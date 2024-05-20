<?php
namespace App\Api\Service\Module;

use App\Api\Service\PlayerService;
use App\Api\Service\BattleService;
use App\Api\Utils\Consts;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigMagic;
use App\Api\Table\ConfigMagicLevelUp;
use App\Api\Table\ConfigCombine;
use App\Api\Table\ConfigSkill;
use App\Api\Table\ConfigStone;
use EasySwoole\Component\CoroutineSingleTon;

class MagicService
{
    use CoroutineSingleTon;

    public function initMagic(PlayerService $playerSer):void
    {
        if($magic = $playerSer->getData('magic')) return;

        $magic = [
            'active'    => [1 => 0, 2 => 0, 3 => 0, 4 => 0], // 1=兵法（道法） 2=攻心（神识） 3=统御（驭兽） 4=体魄（躯体）
            'bag'       => [],  // 1100 => ['id' => 1100, 'lv' => 1, 'stage' => 1, 'stone' => []]
            'combine'   => $this->getCombine(),
        ];

        $playerSer->setMagic('', 0, $magic, 'flushall');
    }

    public function dailyReset(PlayerService $playerSer):void
    {
        //重置每日
        $playerSer->setArg(Consts::MAGIC_AD_TAG,1,'unset');
        $playerSer->setArg(Consts::MAGIC_FREE_COUNT,1,'unset');
    }

    public function getCombine():array
    {
        $combine = ConfigCombine::getInstance()->getTypeAll(2);

        $combines = [];
        foreach($combine as $bine){
            $combines[$bine] = 0;
        }

        return $combines;
    }

    // public function getActive():array
    // {
    //     $array      = ConfigMagic::getInstance()->getAll();

    //     $active     = [];
    //     foreach ($array as $item) {
    //         $active[$item['type']] = 0;
    //     }

    //     return $active;
    // }

    public function getStateCombine($id, $bag)
    {
        if(empty($bag)) return 0;

        $combines   = ConfigMagic::getInstance()->getAllCombineId();
        $magic_ids  = $combines[$id];

        $where = [];
        foreach($magic_ids as $magic_id => $val)
        {
            if(array_key_exists($magic_id,$bag))
            {
                if($bag[$magic_id]['stage'] > 0) $where[$magic_id] = $val;
            }
        }

        $unmatchedKeys = array_diff_key($magic_ids, $where);

        return empty($unmatchedKeys) ? 1 : 0;
    }

    public function getUpCombine($id, $combine, $bag)
    {
        if(empty($bag)) return 0;

        $config      = ConfigParam::getInstance()->getFmtParam("MAGIC_COMBINE_LEVEL");
        $combine_lv  = $combine[$id];
        $where_lv    = $config[$combine_lv];
        
        $combines   = ConfigMagic::getInstance()->getAllCombineId();
        $magic_ids  = $combines[$id];

        $where = [];
        foreach($magic_ids as $magic_id => $val){
            if(array_key_exists($magic_id,$bag))
            {
                if($bag[$magic_id]['stage'] >= $where_lv) $where[$magic_id] = $val;
            }
        }

        $unmatchedKeys = array_diff_key($magic_ids, $where);

        return empty($unmatchedKeys) ? 1 : 0;
    }

    public function getMagicLevelLimit($lv, $stage):int
    {
        $where  = 0;

        $max = ConfigParam::getInstance()->getFmtParam("MAGIC_LEVEL_LIMIT") + 0;
        if($lv == $max) return $where;

        $config = ConfigMagicLevelUp::getInstance()->getOne($lv);
 
        if($stage >= $config['unlock']) $where  = 1;

        return $where;
    }

    public function getMagicFmtData(PlayerService $playerSer):array
    {
        $magicData = $playerSer->getData('magic');

        $config_props      = ConfigParam::getInstance()->getFmtParam("MAGIC_PULL_COST");
        $config_adCount    = ConfigParam::getInstance()->getFmtParam('MAGIC_AD_LIMIT') + 0;
        $config_freeCount  = ConfigParam::getInstance()->getFmtParam('MAGIC_PULL_FREE_TIME') + 0;
        $config_protect    = ConfigParam::getInstance()->getFmtParam('MAGIC_PULL_PROTECT_TIME') + 0;

        if(empty($magicData)) return [];

        $magic = [
            'active'  => $magicData['active'],
            'bag'     => $magicData['bag'],
            'combine' => $magicData['combine'],
        ];

        $magic['config'] = [
            'gid'       => (int)$config_props['gid'],
            'now_ad'    => $config_adCount - $playerSer->getArg( Consts::MAGIC_AD_TAG ) + 0,
            'free'      => $config_freeCount - $playerSer->getArg( Consts::MAGIC_FREE_COUNT ) + 0,
            'surplus'   => $config_protect - $playerSer->getArg( Consts::MAGIC_REFRESH_COUNT ) + 0,
        ];

        return $magic;
    }

    public function getMagicRandom(PlayerService $playerSer):array
    {
        $magicData = $playerSer->getData('magic');

        $playerSer->setArg(Consts::MAGIC_REFRESH_COUNT,1,'add'); // 累计抽奖数
        $config_protect = ConfigParam::getInstance()->getFmtParam('MAGIC_PULL_PROTECT_TIME') + 0;

        if($config_protect == $playerSer->getArg( Consts::MAGIC_REFRESH_COUNT )) // 保底了
        {
            $config_magic   = ConfigMagic::getInstance()->getQuality(ConfigParam::getInstance()->getFmtParam('MAGIC_PULL_PROTECT_QUALITY'));// 保底品质
            $magic_id       = array_rand($config_magic);
            $magic          = $config_magic[$magic_id];
            if(array_key_exists($magic_id,$magicData['bag'])) // 已有神通转换为碎片 (等级最高碎片溢出,碎片转换为其他道具)
            {   
                $id      = $magic_id;
                $itemId  = $magic['item_id'];
                $itemNum = ConfigParam::getInstance()->getFmtParam('MAGIC_REPEAT_PARAM') + 0;
            }else{
                $id      = $magic_id;
                $itemId  = $magic['item_id'];
                $itemNum = 1;
            }
            for($i = 1; $i <= $magic['stone_num']; $i++)
            {
                $itemStone[$i] = 0;
            }
            $playerSer->setArg(Consts::MAGIC_REFRESH_COUNT,1,'unset'); // 清空保底
        }else{
            $config_weight  = ConfigParam::getInstance()->getFmtParam('MAGIC_PULL_WEIGHT');
            $quality        = randTable($config_weight) + 1; // 下标 + 1
            $config_magic   = ConfigMagic::getInstance()->getQuality($quality);
            $magic_id       = array_rand($config_magic);
            $magic          = $config_magic[$magic_id];
            if(array_key_exists($magic_id,$magicData['bag'])) // 已有神通转换为碎片 (等级最高碎片溢出,碎片转换为其他道具)
            {   
                $id      = $magic_id;
                $itemId  = $magic['item_id'];
                $itemNum = ConfigParam::getInstance()->getFmtParam('MAGIC_REPEAT_PARAM') + 0;
            }else{
                $id      = $magic_id;
                $itemId  = $magic['item_id'];
                $itemNum = 1;
            }
            for($i = 1; $i <= $magic['stone_num']; $i++)
            {
                $itemStone[$i] = 0;
            }
        }

        return [$id,$itemId,$itemNum,$itemStone];
    }

    function aggregateAwards(array $awards):array
    {
        $result = [];

        foreach ($awards as $award) {
            if (isset($award['cost'])) {
                foreach ($award['cost'] as $repeatReward) {
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

    function aggregateReward(array $awards):array
    {
        $result = [];

        foreach ($awards as $repeatReward) {
            $gid = $repeatReward['gid'];
            $num = $repeatReward['num'];

            if (isset($result[$gid])) {
                $result[$gid]['num'] += $repeatReward['num']; // 如果已经存在该 gid，则累加数量
            } else {
                $result[$gid] = $repeatReward; // 否则，添加新的记录
            }
        }
        $resultArray = array_values($result);// 将结果转换为索引数组

        return $resultArray;
    }

    public function getMagicAttrAdd(&$attr,array $magic,&$ratio):void
    {
        if(!$magic) return;

        $active     = $magic['active'];
        $bag        = $magic['bag'];
        $combine    = $magic['combine'];

        //固定加成
        foreach($active as $id)
        {
            if(empty($id)) continue;

            $magic_id   = $bag[$id];
            $config     = ConfigMagicLevelUp::getInstance()->getOne($magic_id['lv']);

            $attr['attack']     = add($attr['attack'],$config['attack']);
            $attr['hp']         = add($attr['hp'],$config['hp']);
            $attr['defence']    = add($attr['defence'],$config['defence']);
        }

        //图鉴技能加成
        $sum = $this->getMagicSkillSum($combine);
        foreach ($sum as $attrName => $attrValue)
        {
            $ratio[$attrName] = add($ratio[$attrName],$attrValue);
        }

        //刻印属性加成
        $sum_stone = $this->getStoneSkillSum($active,$bag);
        foreach ($sum_stone as $attrName => $attrValue)
        {
            $ratio[$attrName] = add($ratio[$attrName],$attrValue);
        }
    }

    public function getMagicSkillSum(array $combine):array
    {

        $map = BattleService::getInstance()->getSkillTypeMap();
        $sum = BattleService::getInstance()->getAttrRatioFmt();

        foreach ($combine as $combine_id => $combine_lv)
        {
            if($combine_lv == 0) continue;

            //1.宠物(副将)羁绊 | 2.神通(阵法)羁绊 | 3.精怪(红颜)羁绊 | 4.法宝(xxxx)羁绊
            $skills         = ConfigCombine::getInstance()->getTypeSkill(2);
            $skillConfig    = ConfigSkill::getInstance()->getOne($skills[$combine_id]);

            $type           = $skillConfig['type'][0];
            $params         = $skillConfig['params'][0];
            $upgradeParams  = $skillConfig['upgradeParams'][0];

            if(array_key_exists($type,$map)){
                $addNum = $params[0] + $upgradeParams[0] * ($combine_lv - 1);
                $sum[$map[$type]]   = add( $sum[$map[$type]], $addNum);
            }
        }

        return $sum;
    }

    public function getStoneSkillSum(array $active, array $bag):array
    {
        $map = BattleService::getInstance()->getSkillTypeMap();
        $sum = BattleService::getInstance()->getAttrRatioFmt();
        
        $skill = [];
        foreach($active as $index => $id)
        {
            if(empty($id)) continue;
            $stone = $bag[$id]['stone'];

            foreach($stone as $stone_id)
            {
                if(empty($stone_id)) continue;
                $config = ConfigStone::getInstance()->getOne($stone_id);
                $skill[] = $config['effect_id'];
            }
        }

        foreach ($skill as $k => $v)
        {
            $skillConfig = ConfigSkill::getInstance()->getOne($v);

            $type           = $skillConfig['type'][0];
            $params         = $skillConfig['params'][0];

            if(array_key_exists($type,$map)){
                $addNum = $params[0];
                $sum[$map[$type]]   = add( $sum[$map[$type]], $addNum);
            }
        }

        return $sum;
    }

    public function getMagicRedPointInfo(PlayerService $playerSer):array
    {
        $magic  = $playerSer->getData('magic');

        //衍化神通 (免费|道具)
        $draw = $up = $stage = $ih = false;
        $config_freeCount   = ConfigParam::getInstance()->getFmtParam('MAGIC_PULL_FREE_TIME') + 0; //免费衍化神通
        $cost               = ConfigParam::getInstance()->getFmtParam('MAGIC_PULL_COST');
        if($config_freeCount - $playerSer->getArg( Consts::MAGIC_FREE_COUNT ) || $playerSer->getGoods($cost['gid']) > $cost['num'])
        {
            $draw = true;
        }

        //神通升级
        foreach($magic['bag'] as $magic_id => $magic_val)
        {
            //满级则跳出
            $where_level_limit  = $this->getMagicLevelLimit($magic_val['lv'], $magic_val['stage']);
            if(empty($where_level_limit)) continue;

            $up_consume = ConfigMagicLevelUp::getInstance()->getOne($magic_val['lv']);
            $cost       = $up_consume['cost'];
            if($playerSer->getGoods($cost[0]['gid']) >= $cost[0]['num'] && $playerSer->getGoods($cost[1]['gid']) >= $cost[1]['num'])
            {
                $up = true;
            }
        }

        //神通进阶
        $upgrade = ConfigParam::getInstance()->getFmtParam('MAGIC_UPGRADE_PARAM');
        foreach($magic['bag'] as $magic_id => $magic_val)
        {
            $config   = ConfigMagic::getInstance()->getOne($magic_id);

            if($magic_val['stage'] == count($upgrade) + 1) continue;
            if($playerSer->getGoods($config['item_id']) >= $upgrade[$magic_val['stage'] - 1])
            {
                $stage = true;
            }
        }

        //图鉴(激活|升级)
        foreach($magic['combine'] as $combine_id => $combine_val)
        {
            $config = ConfigParam::getInstance()->getFmtParam("MAGIC_COMBINE_LEVEL");
            if($combine_val == count($config) + 1) continue;

            if($combine_val == 0)
            {
                $state  = $this->getStateCombine($combine_id,$magic['bag']);
                if($state) $ih = true;
            }else{
                $state  = $this->getUpCombine($combine_id,$magic['combine'],$magic['bag']);
                if($state) $ih = true;
            }
        }

        return [$draw,$up,$stage,$ih];
    }


    public function getMagicList($magicData){
        $magicList = array();
        foreach ( $magicData['active'] as $key=>$id){
            $data = array();
            $data['id'] = $id;
            if($id == 0){
                $data['lv'] = 0;
                $data['skill_id'] = 0;
            }else{
                $data['lv'] = $magicData['bag'][$id]['stage'];
                $data['skill_id'] = ConfigMagic::getInstance()->getOne($id)['effect_id'];
            }
            $magicList[$key] = $data;
        }
        return $magicList;
    }


}
