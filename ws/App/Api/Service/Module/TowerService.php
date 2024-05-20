<?php
namespace App\Api\Service\Module;

use App\Api\Service\BattleService;
use App\Api\Service\PlayerService;
use App\Api\Utils\Consts;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigSkill;
use App\Api\Table\ConfigSkillRandom;
use App\Api\Table\ConfigTower;
use EasySwoole\Component\CoroutineSingleTon;

class TowerService
{
    use CoroutineSingleTon;

    public function initTower(PlayerService $playerSer):void
    {
        //解锁初始化
        if($tower = $playerSer->getData('tower')) return ;

        $tower = [
            'towerid'   => 1,
            'buffnum'   => 0,
            'bufftemp'  => [],
            'buff'      => [],
            'preinst'   => [], //预设
            'open'      => 0,  //预设开关
            'record'    => [], //记录获取过BUFF的关卡
        ];

        $playerSer->setTower('',0,$tower,'flushall');
    }

    public function dailyReset(PlayerService $playerSer):void
    {
        //每日重置
        $init = [
            'towerid'   => 1,
            'buffnum'   => 0,
            'bufftemp'  => [],
            'buff'      => [],
            'preinst'   => [],
            'open'      => 0,
            'record'    => [],
        ];
        $playerSer->setTower('', 0, $init, 'flushall');
    }

    public function getSkillRandom(int $num,array $filter):array
    {
        $skill = [];
        
        list($skills, $weight) = ConfigSkillRandom::getInstance()->getAllWeight(1,$filter);
        for ($i=0; $i < $num; $i++) 
        { 
            $index = randTable($weight);
            $skill[ $skills[$index] ] = 1 ;
            unset($weight[$index],$skills[$index]);
        }
        return $skill;
    }
    
    public function getTowerFmtData(PlayerService $playerSer):array
    {
        $towerData = $playerSer->getData('tower');

        $tower = [
            'towerid'   => $towerData['towerid'],
            'pass_id'   => $towerData['towerid'] - 1,
            'buffnum'   => $towerData['buffnum'],
            'bufftemp'  => $towerData['bufftemp'],
            'buff'      => $this->buffFmtData($towerData['buff']),
            'preinst'   => $towerData['preinst'],
            'open'      => $towerData['open'],
            'high'      => $playerSer->getArg( Consts::TOWER_HIGH_RECORD ),
        ];

        return $tower;
    }

    public function buffFmtData($buff)
    {
        $buffFmtData = [];
        foreach($buff as $k => $v)
        {
            $buffFmtData[] = ['id' => $k, 'lv' => $v];
        }
        return $buffFmtData;
    }

    public function recordFloorBuff(int $towerid, array $towers):int
    {
        $where = 1;
        if(in_array($towerid, $towers))
        {
            $where = 0;
        }
        return $where;
    }
    
    public function getTowerIdGainBuff(int $towerid):int
    {
        $where = 0;
        if($towerid == 1) return $where;

        $floor = $towerid % 10;
        if($floor == 0)
        {
            $where = 1;
        }
        return $where;
    }

    public function getTierWhetherBuff(int $towerid):array
    {
        $buff = [];
        if($towerid == 1) return $buff;

        $floor = $towerid % 10;
        if($floor == 1)
        {
            $buff = $this->getSkillRandom(3, []);
        }

        return $buff;
    }

    public function replaceTowerBuff(array $buff, int $temp, int $long):array
    {
        $temp_buff = [$temp => 1]; //技能默认等级

        $index = array_search($long, array_keys($buff)); // 寻找要插入的位置

        $buff = array_slice($buff, 0, $index, true) + $temp_buff + array_slice($buff, $index, null, true); // 填入新数组

        unset($buff[$long]);

        return $buff;
    }

    public function openPreinst(PlayerService $playerSer):void
    {
        // admin:
        // 以下全为百分比
        // 1001=攻击 1002=生命 1003=防御 1004=敏捷 1005=击晕 1006=暴击 1007=连击 1008=闪避 1009=反击 1010=吸血
        // 1011=抗击晕 1012=抗暴击 1013=抗连击 1014=抗闪避 1015=抗反击 1016=抗吸血 
        // 1017=最终增伤 1018=最终减伤 1019=强化爆伤 1020=弱化爆伤 1021=强化治疗 1022=弱化治疗
        // 1023=强化灵兽 1024=弱化灵兽 1025=强化战斗抗性 1027=强化战斗属性

        $tower      = $playerSer->getData('tower');
        $buff       = $tower['buff'];
        $bufftemp   = $tower['bufftemp'];
        $preinst    = $tower['preinst'];
        $towerid    = $tower['towerid'];
        $buffnum    = $tower['buffnum'];

        $config     = ConfigTower::getInstance()->getOne($towerid);

        if(empty($bufftemp)) return;

        //判断临时BUFF预设优先级
        foreach($bufftemp as $skillid => $lv)
        {
            $skillConfig = ConfigSkill::getInstance()->getOne($skillid);
            $buff_temp[] = $skillConfig;
        }

        $buff_sort  = $this->buffCustomSort($buff_temp,$preinst); //排序出最高优先级BUFF
        $ssr        = $buff_sort[0];

        // 有相同BUFF升级
        if(array_key_exists($ssr['id'],$buff))
        {
            $old        = $buff[$ssr['id']];
            if($old < $ssr['maxLevel']) $old++;

            $playerSer->setTower('buff',$ssr['id'],$old,'multiSet');
        }else{
            // 同类型高品质替换
            list($temp,$long) = $this->buffStar($buff, $ssr);
            if($long)
            {
                $buff_set = $this->replaceTowerBuff($buff, $temp, $long);
                $playerSer->setTower('buff',0,$buff_set,'set');
            }else{
                // 追加
                if(count($buff) < $config['buff_limit'])
                {
                    $playerSer->setTower('buff',$ssr['id'],1,'multiSet');
                }else{
                    list($temp_screen,$long_screen) = $this->buffScreen($buff, $ssr, $preinst);
                    if($long_screen)
                    {
                        $buff_set = $this->replaceTowerBuff($buff, $temp_screen, $long_screen);
                        $playerSer->setTower('buff',0,$buff_set,'set');
                    }
                }
            }
        }
        $playerSer->setTower('bufftemp',0,[],'set');
    }

    // public function buffCustomSort(array $skills, array $presetTypes):array
    // {
    //     // 自定义排序逻辑
    //     usort($skills, function ($a, $b) use ($presetTypes) {
    //         $aTypeIndex = array_search($a['type'][0], $presetTypes);
    //         $bTypeIndex = array_search($b['type'][0], $presetTypes);

    //         // 检查type是否在预设数组中，并进行排序
    //         if ($aTypeIndex !== false && $bTypeIndex !== false) {
    //             if ($aTypeIndex == $bTypeIndex) {
    //                 return $b['star'] - $a['star']; // 如果type相同，则根据star降序排序
    //             }
    //             return $aTypeIndex - $bTypeIndex;
    //         } elseif ($aTypeIndex !== false) {
    //             return -1; // a在预设数组中，排前面
    //         } elseif ($bTypeIndex !== false) {
    //             return 1; // b在预设数组中，排前面
    //         } elseif ($a['type'] == $b['type']) {
    //             return $b['star'] - $a['star']; // 如果type相同且都不在预设数组中，则根据star降序排序
    //         }
    //         return 0; // 默认情况（两个type都不在预设数组中，且不相同），不需要额外排序
    //     });
    //     return $skills;
    // }

    public function buffCustomSort(array $skills, array $presetTypes):array
    {
        // 自定义排序逻辑
        usort($skills, function ($a, $b) use ($presetTypes) {
            $aTypeIndex = array_search($a['type'][0], $presetTypes);
            $bTypeIndex = array_search($b['type'][0], $presetTypes);

            // 检查type是否在预设数组中，并进行排序
            if ($aTypeIndex !== false && $bTypeIndex !== false) {
                if ($aTypeIndex == $bTypeIndex) {
                    return $b['star'] - $a['star']; // 如果type相同，则根据star降序排序
                }
                return $aTypeIndex - $bTypeIndex;
            } elseif ($aTypeIndex !== false) {
                return -1; // a在预设数组中，排前面
            } elseif ($bTypeIndex !== false) {
                return 1; // b在预设数组中，排前面
            }

            // 默认情况，两个type都不在预设数组中，且不相同，根据star降序排序
            return $b['star'] - $a['star'];
        });
        return $skills;
    }

    public function buffStar(array $skills, array $ssr):array
    {
        $temp = 0;
        $long = 0;
        foreach($skills as $skillid => $lv)
        {
            $skillConfig = ConfigSkill::getInstance()->getOne($skillid);

            if($skillConfig['type'][0] != $ssr['type'][0]) continue;

            if($skillConfig['star'] > $ssr['star']) continue;

            $long = $skillConfig['id'];
            $temp = $ssr['id'];
        }

        return [$temp,$long];
    }

    public function buffScreen(array $skills, array $ssr, array $preinst):array
    {
        //筛选上阵BUFF不在预设中且品质比随机低的BUFF
        $temp = 0;
        $long = 0;
        foreach($skills as $skillid => $lv)
        {
            $skillConfig = ConfigSkill::getInstance()->getOne($skillid);

            if(in_array($skillConfig['type'][0],$preinst)) continue;

            if($skillConfig['star'] > $ssr['star']) continue;

            $long = $skillConfig['id'];
            $temp = $ssr['id'];
        }

        return [$temp,$long];
    }

    public function getTowerAttrSum(array $buff):array
    {
        // admin:
        // 以下全为百分比
        // 1001=攻击 1002=生命 1003=防御 1004=敏捷 1005=击晕 1006=暴击 1007=连击 1008=闪避 1009=反击 1010=吸血
        // 1011=抗击晕 1012=抗暴击 1013=抗连击 1014=抗闪避 1015=抗反击 1016=抗吸血 
        // 1017=最终增伤 1018=最终减伤 1019=强化爆伤 1020=弱化爆伤 1021=强化治疗 1022=弱化治疗
        // 1023=强化灵兽 1024=弱化灵兽 1025=强化战斗抗性 1027=强化战斗属性

        $map = BattleService::getInstance()->getSkillTypeMap();
        $sum = BattleService::getInstance()->getAttrRatioFmt();

        // BUFF技能
        foreach ($buff as $skillid => $lv)
        {
            $skillConfig = ConfigSkill::getInstance()->getOne($skillid);
            $type           = $skillConfig['type'][0];
            $params         = $skillConfig['params'][0];
            $upgradeParams  = $skillConfig['upgradeParams'][0];

            if(array_key_exists($type,$map)){
                $addNum = $params[0] + $upgradeParams[0] * ($lv - 1);
                $sum[$map[$type]]   = add( $sum[$map[$type]], $addNum);
            }
        }

        return $sum;
    }

    function aggregateAwards(array $awards):array
    {
        $result = [];

        foreach ($awards as $level => $award) {
            if (isset($award['repeat_rewards'])) {
                foreach ($award['repeat_rewards'] as $repeatReward) {
                    $gid = $repeatReward['gid'];
                    $num = $repeatReward['num'];

                    if (isset($result[$gid])) {
                        $result[$gid]['num'] += $repeatReward['num']; // 如果已经存在该 gid，则累加数量
                    } else {
                        $result[$gid] = $repeatReward; // 否则，添加新的记录
                    }
                }
            }
        }
        $resultArray = array_values($result);// 将结果转换为索引数组

        return $resultArray;
    }

    public function getTowerRedPointInfo(PlayerService $playerSer):array
    {
        $tower    = $playerSer->getData('tower');

        $zhangdou = false;
        $preinst  = false;
        if($tower['towerid'] == 1){
            $zhangdou   = true;
        }

        return [$zhangdou,$preinst];
    }
}
