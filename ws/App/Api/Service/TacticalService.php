<?php

namespace App\Api\Service;

use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigTalent;
use App\Api\Table\ConfigTalentBook;
use App\Api\Table\ConfigTalentLevel;
use App\Api\Table\ConfigTalentCreate;
use App\Api\Utils\Consts;
use EasySwoole\Component\CoroutineSingleTon;
use EasySwoole\EasySwoole\Logger;

/**
 * 阵法
 */
class TacticalService
{
    use CoroutineSingleTon;

    /**
     * 是否解锁
     * @param PlayerService $playerSer
     * @return bool
     */
    public function isUnlock(PlayerService $playerSer): bool
    {
        $taskNum = $playerSer->getArg(COUNTER_TASK);
        //判断解锁条件,关卡
        if ($taskNum < 164) {
            return false;
        }
        return true;
    }

    /**
     * 初始化用户阵法数据
     * @param PlayerService $playerSer
     * @return void
     * @throws \Exception
     */
    public function initTactical(PlayerService $playerSer): void
    {
        //解锁初始化
        if ($tacticalInfo = $playerSer->getData('tactical')) {
            //获取对应的升级所需要经验
            $tactical['next_lv'] = $this->getNextLvNeedExp($tacticalInfo['level']['lv']);
            $playerSer->setTactical('next_lv', 0, $tactical['next_lv'], 'set');
            return;
        }
        //处理旧用户的阵法数据
        $tacticalInfo = $this->getInitTactical();
        $playerSer->setTactical('', 0, $tacticalInfo, 'flushall');

    }


    /**
     * 获取阵法初始化参数
     * @return array
     */
    public function getInitTactical(): array
    {
        $tactical = [
            'afflatus' => 1, //灵感
            'list' => [1 => [], 2 => [], 3 => [], 4 => [], 5 => [], 6 => [], 7 => [],
                8 => [], 9 => [], 10 => [], 11 => [], 12 => []],
            'tmp' => [],  //未使用阵眼
            'level' => ['lv' => 1, 'exp' => 0],
        ];
        $tactical['next_lv'] = $this->getNextLvNeedExp(1);
        return $tactical;
    }


    /**
     * 获取阵法等级升级所需要经验
     * @param int $lv
     * @return mixed
     */
    public function getNextLvNeedExp(int $lv)
    {
        $config = ConfigTalentCreate::getInstance()->getOne($lv);
        return $config['exp'];
    }


    //抽取阵眼
    public function lotteryEye($tacticalInfo): array
    {
        //等级配置    TalentCreate id：talentLevel 1;20|2;10|3;5
        $createConfig = ConfigTalentCreate::getInstance()->getOne($tacticalInfo['level']['lv']);
        //处理数据变成适用randTable方法
        $list = array();
        foreach ($createConfig['talent_level'] as $value) {
            $list[$value[0]] = $value[1];
        }
        $lv = randTable($list);
        if (!$lv) {
            Logger::getInstance()->waring('抽取配置：' . json_encode($list));
            Logger::getInstance()->waring('等级lv' . $lv);
        }

        //稀有度配置  talenBook--id：weight 0|0|0|0|642015|269907|42489|27597|17992
        $bookConfig = ConfigTalentBook::getInstance()->getOne($tacticalInfo['afflatus']);
        $list = array();
        foreach ($bookConfig['weight'] as $key => $value) {
            $list[$key + 1] = $value;
        }
        $quality = randTable($list);
        if (!$quality) {
            Logger::getInstance()->waring('抽取配置：' . json_encode($list));
            Logger::getInstance()->waring('品质quality' . $quality);
        }


        //抽阵眼 talent,通过品质和等级,抽取合适的阵眼
        $eyeInfo = ConfigTalent::getInstance()->lotteryEyeToQualityAndLv($quality, $lv);
        if (!$eyeInfo) {
            Logger::getInstance()->waring('抽取阵眼：' . json_encode($eyeInfo));
        }
        //$eyeInfo = ['type' => 5, 'icon' => 1000, 'quality' => 3,];


        //词条数配置 TalentLevel  level：basicCount 1；
        $levelConfig = ConfigTalentLevel::getInstance()->getOne($lv);
        $basicCount = $levelConfig['basic_count'];//需要的词条数量

        //抽取词条
        $attributeIds = array(); //抽取的词条id数组
        $list = array();
        foreach ($eyeInfo['fir_attribute_ran'] as $key => $value) {
            if ($value == 0) continue;
            if ($value == -1) {
                $attributeIds[] = $key;
            } else {
                $list[$key] = $value;
            }
        }

        while (count($attributeIds) < $basicCount) {
            $attributeId = randTable($list);
            $attributeIds[] = $attributeId;
            unset($list[$attributeId]);
        }


        //对属性词条赋值
        $attribute = array();
        foreach ($attributeIds as $id) {
            $data = array();
            switch ($id) {
                case 0:
                case 1:
                case 2:
                case 3:
                    //处理基础属性
                    $data['type'] = 1;
                    $data['litye'] = $id + 1;//1-4
                    $flied = $eyeInfo[Consts::BASIC_ATTRIBUTE[$data['litye'] - 1]];
                    $val = rand($flied[0], $flied[1]);
                    $data['val'] = div($levelConfig['basic'] * $val, 1000);
                    break;
                case 4:
                case 5:
                case 6:
                case 7:
                case 8:
                case 9:
                    $data['type'] = 3;
                    $data['litye'] = $id - 3;//1-6
                    $flied = $eyeInfo[Consts::SECOND_ATTRIBUTE[$data['litye'] - 1]];
                    $val = rand($flied[0], $flied[1]);
                    $data['val'] = div($levelConfig['second'] * $val, 1000);
                    break;
                case 10:
                case 11:
                case 12:
                case 13:
                case 14:
                case 15:
                    $data['type'] = 4;
                    $data['litye'] = $id - 9;//1-6
                    $flied = $eyeInfo[Consts::SECOND_DEF_ATTRIBUTE[$data['litye'] - 1]];
                    $val = rand($flied[0], $flied[1]);
                    $data['val'] = div($levelConfig['second_def'] * $val, 1000);
                    break;
            }
            $attribute[] = $data;
        }


        /**
         * 阵眼 附带技能
         * 如果没有技能 'skill' = -1；
         */
        $skill = -1;
        //灵脉特殊类型 3|5|9|11
        $specialTypeConfig = ConfigParam::getInstance()->getFmtParam('TALENT_SPECIAL_TYPE');
        //灵脉特殊影响（技能） 50001|50002|50003|50004|50005|50006
        $specialSkillConfig = ConfigParam::getInstance()->getFmtParam('TALENT_SPECIAL_EFFECT');
        if (in_array($eyeInfo['type'], $specialTypeConfig)) {
            $list = [];
            foreach ($specialSkillConfig as $value) {
                $list[$value] = 1;
            }
            $skill = randTable($list);
        }

        //id ,技能，类型，等级,头像，名称，品质，属性，
        $info = ['id' => $eyeInfo['id'], 'skill' => $skill, 'type' => $eyeInfo['type'], 'lv' => $lv, 'icon' => $eyeInfo['icon'],
            'name' => $eyeInfo['name'], 'quality' => $eyeInfo['quality'], 'attribute' => $attribute,];

        return $info;
    }


    /**
     * 回收阵眼
     * @param PlayerService $playerSer
     * @param int $lv 用户当前阵法等级
     * @return array
     * @throws \Exception
     */
    public function RecoveryEye(PlayerService $playerSer, int $lv): array
    {
        $config = ConfigTalentLevel::getInstance()->getOne($lv);
        $reward = array();
        $comradeBuff = $this->getComradeBuff($playerSer);
        foreach ($config['reward'] as $value) {
            $reward[] = ['gid' => $value[0], 'val' => $value[1]];
            $number = $value[1];
            $id = $value[0];
            if ($comradeBuff > 0) {
                $buff = div($value[1] * $comradeBuff, 1000);
                $number = add($buff, $value[1]);
            }
            
            $reward = [ [ 'type' => GOODS_TYPE_1,'gid' => $id,'num' => $number ] ];
            $playerSer->goodsBridge($reward,'阵眼回收',$playerSer->getGoods($id));
        }

        return $reward;
    }

    //计算仙游buff
    public function getComradeBuff(PlayerService $playerSer)
    {
        //回收阵眼，增加悟性
        return ComradeService::getInstance()->getLvStageByTalentCopy($playerSer, 60002);
    }


    /**
     * 判断阵法红点
     * @param PlayerService $playerSer
     * @return boolean[]
     */
    public function getRedPointInfo(PlayerService $playerSer): array
    {

        $res = array();

        //获取阵法参悟配置 物品id；数量
        $readConfig = ConfigParam::getInstance()->getFmtParam('TALENT_READ_COST');
        $hasNum = $playerSer->getGoods($readConfig['gid']);
        $number = $readConfig['num'];
        if ($number <= $hasNum) {
            array_push($res, true);
        } else {
            array_push($res, false);
        }

        //获取阵法推演配置 物品id；数量
        $pullConfig = ConfigParam::getInstance()->getFmtParam('TALENT_PULL_COST');
        $hasNum = $playerSer->getGoods($pullConfig['gid']);
        $number = $pullConfig['num'];
        if ($number <= $hasNum) {
            array_push($res, true);
        } else {
            array_push($res, false);
        }

        return $res;
    }


    //处理阵法属性
    public function getTacticalAttrAdd(&$attr, $tactical): void
    {
        if (!$tactical) return;

        //处理属性
        foreach ($tactical['list'] as $detail) {
            if (!$detail) continue;
            //处理基础属性
            foreach ($detail['attribute'] as $value) {
                switch ($value['type']) {
                    case 1://处理基础属性
                        $name = substr(Consts::BASIC_ATTRIBUTE[$value['litye'] - 1], 5);
                        //prim_
                        $attr[$name] = add($attr[$name], $value['val']);
                        break;
                    case 3://处理第二词条属性
                        $name = Consts::SECOND_ATTRIBUTE[$value['litye'] - 1];
                        $attr[$name] = add($attr[$name], $value['val']);
                        break;
                    case 4://处理第二词条抗性属性
                        $name = Consts::SECOND_DEF_ATTRIBUTE[$value['litye'] - 1];
                        $attr[$name] = add($attr[$name], $value['val']);
                        break;
                }
            }

        }
    }


    /**
     * 获取阵法附带的技能以及等级
     * @param array $tactical 阵法数据
     * @return array
     */
    public function getTacticalSkill(array $tactical): array
    {
        $skillList = array();

        foreach ($tactical['list'] as $detail) {
            if ($detail && $detail['skill'] > 1) {
                if (isset($skillList[$detail['skill']])) {
                    $skillList[$detail['skill']]++;
                } else {
                    $skillList[$detail['skill']] = 1;
                }
            }
        }
        return $skillList;

    }

}