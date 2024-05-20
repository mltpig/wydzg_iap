<?php
namespace App\Api\Service\Module;

use App\Api\Service\BattleService;
use App\Api\Service\PlayerService;
use App\Api\Utils\Consts;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigSkill;
use App\Api\Table\ConfigSkillRandom;
use App\Api\Table\ConfigSpirits;
use App\Api\Table\ConfigCombine;
use EasySwoole\Component\CoroutineSingleTon;

class SpiritService
{
    use CoroutineSingleTon;

    public function initSpirit(PlayerService $playerSer):void
    {
        //精怪内容解锁
        if($spirit = $playerSer->getData('spirit')) return;

        $bag = [];

        //1.宠物(副将)羁绊 | 2.神通(阵法)羁绊 | 3.精怪(红颜)羁绊 | 4.法宝(xxxx)羁绊
        $combine = ConfigCombine::getInstance()->getTypeAll(3);

        $yoke = [];
        foreach($combine as $bine){
            $yoke[$bine] = 0;
        }

        $spirit = [
            'active'    => 0,
            'bag'       => $bag,
            'groove'    => 1,
            'squad'     => [[], [], []],
            'yoke'      => $yoke,
        ];

        $playerSer->setSpirit('', 0, $spirit, 'flushall');
    }

    public function dailyReset(PlayerService $playerSer):void
    {
        //重置每日广告次数
        $playerSer->setArg(Consts::SPIRIT_AD_TAG,1,'unset');
    }

    public function getBagSpiritInitFmtData(int $spiritid):array
    {
        return [
            'id' => $spiritid, 'state' => 0, 'lv' => 0, 'now_debris' => 0, 'max_debris' => 0,
            'active_skill' => $this->getSkill('active',$spiritid), 'passive_skill' => $this->getSkill('passive',$spiritid),
        ];
    }

    public function getStateFetter(int $id, array $bag):int
    {
        if(empty($bag)) return 1;

        $combines   = ConfigSpirits::getInstance()->getAllCombineId();
        $spiritids  = $combines[$id];

        foreach($spiritids as $spiritid => $val){
            if(array_key_exists($spiritid,$bag))
            {
                if($bag[$spiritid]['state'] > 0) $where[$spiritid] = $val;
            }
        }

        if(empty($where)) return 1;

        $unmatchedKeys = array_diff_key($spiritids, $where);
        if(empty($unmatchedKeys)){
            return 0;
        }else{
            return 1;
        }
    }

    public function getUpFetter(int $id, array $yoke, array $bag):int
    {
        if(empty($bag)) return 1;

        $upLv       = explode("|",'1|2|3|4|5|7|9|11|13|15|17|20|23|26|30|34|38|42|46|50|55|60'); //升级条件数值写死
        $yokeLv     = $yoke[$id];

        if($yokeLv == 0) return 1;
        if($yokeLv == 22) return 1;

        $spiritLv   = $upLv[$yokeLv];

        $combines   = ConfigSpirits::getInstance()->getAllCombineId();
        $spiritids  = $combines[$id];

        foreach($spiritids as $spiritid => $val){
            if(array_key_exists($spiritid,$bag))
            {
                if($bag[$spiritid]['lv'] >= $spiritLv) $where[$spiritid] = $val;
            }
        }

        if(empty($where)) return 1;

        $unmatchedKeys = array_diff_key($spiritids, $where);
        if(empty($unmatchedKeys)){
            return 0;
        }else{
            return 1;
        }
    }

    public function getStateDiff(array $ids, array $bag):int
    {
        if(empty($bag)) return 1;

        foreach($bag as $k => $v){
            if($v['state'] > 0) $where[] = $k;
        }

        if(empty($where)) return 1;

        $elementsNotInArray = array_diff($ids, $where);

        if(empty($elementsNotInArray)){
            return 0;
        }else{
            return 1;
        }
    }

    public function getSkill(string $type, int $id):array
    {
        $config = ConfigSpirits::getInstance()->getOne($id);
        if($type == "active"){
            return [$config['active_skill'] => 1];
        }else{
            $skills = [];
            $arr = explode('|',$config['passive_skill']);
            foreach($arr as $index){
                $skills[$index] = 0;
            }
            return $skills;
        }
    }

    // public function getUpSkill(int $lv, $passive, $active):array
    // {
    //     $passive_skill = explode("|",$passive);
    //     $active_skill  = explode("|",$active);

    //     $skills = array_merge_recursive($passive_skill,$active_skill);

    //     // 计算对应升级技能数组下标
    //     $index = ($lv - 1) % count($skills);
    //     // 获取对应升级技能
    //     $upgradedSkill = $skills[$index];

    //     $type = $index == 4 ? 'active_skill' : 'passive_skill';

    //     return [$type, $upgradedSkill];
    // }
    
    function getUpSkill(int $lv, $passive, $active):array
    {
        $passive_skill = explode("|",$passive);
        $active_skill  = explode("|",$active);
    
        $skills = array_merge_recursive($passive_skill,$active_skill);
    
        $skill1 = $skill2 = [];
        for($i=1; $i <= $lv; $i++)
        {
            // 计算对应升级技能数组下标
            $index = ($i - 1) % count($skills);
            // 获取对应升级技能
            $upgradedSkill = $skills[$index];
            $type = $index == 4 ? 'active_skill' : 'passive_skill';
            if($type == 'active_skill')
            {
                if(isset($skill2[$upgradedSkill])){
                    $skill2[$upgradedSkill] += 1;
                }else{
                    $skill2[$upgradedSkill] = 1;
                }
            }else{
                if(isset($skill1[$upgradedSkill])){
                    $skill1[$upgradedSkill] += 1;
                }else{
                    $skill1[$upgradedSkill] = 1;
                }
            }
        }
    
        $passive_value = $active_value = [];
        foreach($passive_skill as $val){
            $lv = isset($skill1[$val]) ? $skill1[$val] : 0;
            $passive_value[$val] = $lv; //被动默认0级
        }
    
        foreach($active_skill as $val){
            $lv = isset($skill2[$val]) ? $skill2[$val] : 0;
            $active_value[$val] = ($lv + 1); //主动默认1级
        }
    
        return [$passive_value,$active_value];
    }

    public function getInitSpiritRandom():array
    {
        $first_pull_ensure_quality = ConfigParam::getInstance()->getFmtParam("SPIRIT_FIRST_PULL_ENSURE_QUALITY");

        foreach($first_pull_ensure_quality as $key){
            $spirits[] = ConfigSpirits::getInstance()->getAllWeight($key);
        }

        // $list = array_merge_recursive($spirits[0], $spirits[1]); 选择循环应对配置表的改动
        $list = [];
        foreach ($spirits as $subArray) {
            foreach ($subArray as $key => $value) {
                $list[$key] = $value;
            }
        }

        $spiritId = randTable($list);
        $config     = ConfigSpirits::getInstance()->getOne($spiritId);
        $compoundItemId = $config['compound_item_id'];
        $spiritNum      = $config['compound_num'] + 0;

        return [$spiritId,$spiritNum,$compoundItemId];
    }

    public function getMinimumGuaranteeSpiritRandom(int $drawCount):array
    {
        $qualitys = $this->calculatePrizeRarity($drawCount - 1); //计算保底类型
        foreach($qualitys as $index => $value){
            $spirits[$index] = ConfigSpirits::getInstance()->getAllWeight($value);
        }

        $list = [];
        foreach ($spirits as $subArray) {
            foreach ($subArray as $key => $value) {
                $list[$key] = $value;
            }
        }

        $spiritId = randTable($list);
        $config     = ConfigSpirits::getInstance()->getOne($spiritId);
        $compoundItemId = $config['compound_item_id'];
        $spiritNum      = $config['compound_num'] + 0;

        return [$spiritId,$spiritNum,$compoundItemId];
    }

    public function getWeightSpiritRandom():array
    {
        $weight = ConfigParam::getInstance()->getFmtParam("SPIRIT_PULL_WEIGHT");
        //index + 1: 品质 | type: 碎片 | 成品
        $randomItem = $this->getRandomItem($weight);
        $index      = $randomItem['index'] + 1;
        $type       = $randomItem['type'];

        $pools      = ConfigSpirits::getInstance()->getAllWeight($index);
        $spiritId   = randTable($pools);

        $config     = ConfigSpirits::getInstance()->getOne($spiritId);
        $compoundItemId = $config['compound_item_id'];

        if($type == "fragment") $spiritNum = 1;
        if($type == "finished"){
          $spiritNum = $config['compound_num'] + 0;
        }

        return [$spiritId,$spiritNum,$compoundItemId];
    }

    public function getSpiritRandom(int $drawCount):array
    {
        //玩家第一次抽取数量
        if($drawCount == ConfigParam::getInstance()->getFmtParam("SPIRIT_FIRST_PULL_ENSURE_NUM")){
           return $this->getInitSpiritRandom();
        }else{
            $count = $drawCount - intval($drawCount / 100) * 100;
            if($count == 0){ //是否保底
                return $this->getMinimumGuaranteeSpiritRandom($drawCount);
            }else{
                return $this->getWeightSpiritRandom();
            }
        }
    }

    public function getSpiritFmtData(PlayerService $playerSer, int $adCount):array
    {
        $spiritData = $playerSer->getData('spirit');
        $drawCount  = $playerSer->getArg( Consts::SPIRIT_DRAW_COUNT );

        $props      = ConfigParam::getInstance()->getFmtParam('SPIRIT_PULL_COST');
        $freeCount  = ConfigParam::getInstance()->getFmtParam('SPIRIT_AD_LIMIT') + 0;

        $spirits = ConfigSpirits::getInstance()->getAll();

        $bag = [];
        foreach($spirits as $id => $value){ 
            if(array_key_exists($id,$spiritData['bag']))
            {
                $bag[$id] = $spiritData['bag'][$id];
            }else{
                $bag[$id] = $this->getBagSpiritInitFmtData($id);
            }
        }

        foreach($bag as $key => $spirit){
            $config = ConfigSpirits::getInstance()->getOne($key);
            $bag[$key]['now_debris'] = $playerSer->getGoods($config['compound_item_id']);

            // 根据状态初始化数据
            if($spirit['state'] == 0)
            {
                $bag[$key]['max_debris'] = $config['compound_num'] + 0;
            }else{
                $levelCost  = ConfigParam::getInstance()->getFmtParam('SPIRIT_LEVEL_COST_NUM');
                $index      = intval($bag[$key]['lv'] / 5);
                if($bag[$key]['lv'] == 60) $index = count($levelCost) - 1;
                $bag[$key]['max_debris'] = $levelCost[$index] + 0;
                
                //技能
                list($passive, $active) = $this->getUpSkill( $bag[$key]['lv'], $config['passive_skill'], $config['active_skill']);
                $bag[$key]['passive_skill'] = $passive;
                $bag[$key]['active_skill']  = $active;
            }
        }

        $surplus = $drawCount - intval($drawCount / 100) * 100;
        list($_sum, $sum) = $this->getSpiritAttrSum($spiritData);

        $spirits = [];
        $spirits['bag']['active']    = $spiritData['active']; //上阵阵容
        $spirits['bag']['list']      = $bag;
        $spirits['bag']['squad']     = $spiritData['squad']; //阵容
        $spirits['bag']['yoke']      = $spiritData['yoke'];  //羁绊
        
        $spirits['config'] = [
            'gid'       => (int)$props['gid'],
            'now_ad'    => $freeCount - $adCount,
            'max_ad'    => $freeCount,
            'surplus'   => 100 - $surplus,          //抽奖剩余N次保底
            'groove'    => $spiritData['groove'],   //解锁格子
            'minimum'   => $this->calculatePrizeRarity($drawCount) //保底品质
        ];

        $spirits['attr_sum'] = $sum;

        return $spirits ? $spirits : [];
    }

    public function getSpiritAttrSum(array $spirits):array
    {
        // admin:
        // 以下全为百分比
        // 1001=攻击 1002=生命 1003=防御 1004=敏捷 1005=击晕 1006=暴击 1007=连击 1008=闪避 1009=反击 1010=吸血
        // 1011=抗击晕 1012=抗暴击 1013=抗连击 1014=抗闪避 1015=抗反击 1016=抗吸血 
        // 1017=最终增伤 1018=最终减伤 1019=强化爆伤 1020=弱化爆伤 1021=强化治疗 1022=弱化治疗
        // 1023=强化灵兽 1024=弱化灵兽 1025=强化战斗抗性 1027=强化战斗属性
        // 6001：灵兽吞噬或者放生时返还8%御灵石
        // 6002：灵脉分解获得材料提升10%
        // 6004：自动锤炼时获得指向属性概率提升8%
        // 6005：斗法获胜时，有8%概率额外获得一个庚金

        // 以下为实际数值（非百分比）
        // 2001=攻击 2002=生命 2003=防御 2004=敏捷
        // 6003：福地鼠宝充沛状态的体力提升10
        // 6006：挑战妖王速战，掉落仙桃数提升20

        $bag    = $spirits['bag'];
        $yoke   = $spirits['yoke'];

        $map = BattleService::getInstance()->getSkillTypeMap();
        $sum = BattleService::getInstance()->getAttrRatioFmt();

        $sumId = [
            1001 => '0',1002 => '0',1003 => '0',1004 => '0',1005 => '0',
            1006 => '0',1007 => '0',1008 => '0',1009 => '0',1010 => '0',
            1011 => '0',1012 => '0',1013 => '0',1014 => '0',1015 => '0',
            1016 => '0',1017 => '0',1018 => '0',1019 => '0',1020 => '0',
            1021 => '0',1022 => '0',1023 => '0',1024 => '0',1025 => '0',
            1027 => '0',2001 => '0',2002 => '0',2003 => '0',2004 => '0',
        ];



        //被动技能
        foreach ($bag as $id => $detail)
        {
            if($detail['state'] == 0) continue;

            $config = ConfigSpirits::getInstance()->getOne($id);
            list($passive, $active) = $this->getUpSkill( $detail['lv'], $config['passive_skill'], $config['active_skill']);

            foreach($passive as $skillid => $lv)
            {
                $skillConfig = ConfigSkill::getInstance()->getOne($skillid);

                //json_decode()
                $type           = $skillConfig['type'][0];
                $params         = $skillConfig['params'][0];
                $upgradeParams  = $skillConfig['upgradeParams'][0];

                if(array_key_exists($type,$map)){
                    $addNum = $params[0] + $upgradeParams[0] * ($lv - 1);
                    $sum[$map[$type]]   = add( $sum[$map[$type]], $addNum);
                    $sumId[$type]       = add( $sumId[$type], $addNum);
                }
            }
        }

        //羁绊技能
        foreach ($yoke as $yokeid => $yokelv)
        {
            if($yokelv == 0) continue;
            //1.宠物(副将)羁绊 | 2.神通(阵法)羁绊 | 3.精怪(红颜)羁绊 | 4.法宝(xxxx)羁绊
            $skills         = ConfigCombine::getInstance()->getTypeSkill(3);
            $skillConfig    = ConfigSkill::getInstance()->getOne($skills[$yokeid]);

            //json_decode()
            $type           = $skillConfig['type'][0];
            $params         = $skillConfig['params'][0];
            $upgradeParams  = $skillConfig['upgradeParams'][0];

            if(array_key_exists($type,$map)){
                $addNum = $params[0] + $upgradeParams[0] * ($yokelv - 1);
                $sum[$map[$type]]   = add( $sum[$map[$type]], $addNum);
                $sumId[$type]       = add( $sumId[$type], $addNum);
            }
        }

        return [$sum, $sumId];
    }

    public function getRandomItem($weightArray):array
    {
        // 按照分号分割碎片权重和成品权重将碎片权重和成品权重分别按照竖线分割
        list($fragmentWeights, $finishedWeights) = $weightArray;
        // 将字符串权重值转换为整数数组
        $fragmentWeights = array_map('intval', $fragmentWeights);
        $finishedWeights = array_map('intval', $finishedWeights);
    
        // 随机选择碎片或成品
        $isFragment = mt_rand(0, array_sum($fragmentWeights) + array_sum($finishedWeights) - 1) < array_sum($fragmentWeights);
    
        // 选择碎片或成品的权重数组
        $weights = $isFragment ? $fragmentWeights : $finishedWeights;
    
        // 使用权重数组进行随机选择
        $index = $this->getRandomWeightedIndex($weights);
    
        // 返回所选项和类型
        return [
            'index' => $index,
            'type'  => $isFragment ? 'fragment' : 'finished'
        ];
    }

    public function getRandomWeightedIndex($weights):int
    {
        $rand = mt_rand(1, array_sum($weights));
    
        foreach ($weights as $index => $weight) {
            if ($rand <= $weight) {
                return $index;
            }
    
            $rand -= $weight;
        }
    
        // 如果未能选择索引，则默认返回最后一个索引
        return count($weights) - 1;
    }

    function calculatePrizeRarity($totalPulls):array
    {
        // 保底随机函数
        $ensureNumList = ConfigParam::getInstance()->getFmtParam('SPIRIT_PULL_ENSURE_NUM');
        $ensureQualityList = ConfigParam::getInstance()->getFmtParam('SPIRIT_PULL_ENSURE_QUALITY');

        // 计算一个完整保底周期的总次数
        $fullCyclePulls = array_sum($ensureNumList);
    
        // 计算当前处于哪个保底周期以及在该周期中的位置
        $cycleIndex = floor($totalPulls / $fullCyclePulls);
        $positionInCycle = $totalPulls % $fullCyclePulls;
    
        // 确定保底阶段
        $accumulatedPulls = 0;
        foreach ($ensureNumList as $i => $num) {
            $accumulatedPulls += $num;
            if ($positionInCycle < $accumulatedPulls) {
                return $ensureQualityList[$i % count($ensureQualityList)];
            }
        }
    
        // 如果没有匹配的条件，默认返回最低稀有度
        return [min($ensureQualityList[0])];
    }

    public function getSpiritRedPointInfo(PlayerService $playerSer):array
    {   
        $role    = $playerSer->getData('role');
        $spirit  = $playerSer->getData('spirit');
        $spirits = ConfigSpirits::getInstance()->getAll();
        
        $hcsj = [];
        foreach($spirits as $key => $value)
        {
            if(array_key_exists($key,$spirit['bag']))
            {
                if($spirit['bag'][$key]['state'] == 0)
                {
                    $goodsNum   = $playerSer->getGoods($value['compound_item_id']);
                    $spiritNum  = $value['compound_num'] + 0;
                    if($goodsNum >= $spiritNum)
                    {
                        $hcsj[] = $key;
                    }
                }
            }else{
                $goodsNum   = $playerSer->getGoods($value['compound_item_id']);
                $spiritNum  = $value['compound_num'] + 0;
                if($goodsNum >= $spiritNum)
                {
                    $hcsj[] = $key;
                }         
            }
        }

        foreach($spirit['bag'] as $key => $value)
        {
            if($value['state'] == 0) continue;

            $levelCost  = ConfigParam::getInstance()->getFmtParam('SPIRIT_LEVEL_COST_NUM');
            $index      = intval($value['lv'] / 5);
            if($value['lv'] == 60) $index = count($levelCost) - 1;
            $max_debris = $levelCost[$index] + 0;

            $config      = ConfigSpirits::getInstance()->getOne($key);

            $goodsNum   = $playerSer->getGoods($config['compound_item_id']);
            if($goodsNum >= $max_debris)
            {
                $hcsj[] = $key;
            }
        }
        $list = array_unique($hcsj);


        $groove = false;
        $where  = ConfigParam::getInstance()->getFmtParam('SPIRIT_BOX_UNLOCK');
        $unlock = $spirit['groove'] + 1;
        if($unlock == 2){
            if($role['lv'] >= $where[1]) $groove = true;
        }else if($unlock == 3){
            if($role['lv'] >= $where[2]) $groove = true;
        }

        $jb = [];
        foreach($spirit['yoke'] as $key => $value)
        {
            if($value > 0) continue;
            $jihuo = $this->getStateFetter($key,$spirit['bag']);
            if(empty($jihuo)) $jb[] = $key;
        }

        foreach($spirit['yoke'] as $key => $value)
        {
            if($value == 0) continue;
            $shengji = $this->getUpFetter($key, $spirit['yoke'], $spirit['bag']);
            if(empty($shengji)) $jb[] = $key;
        }
        $yoke = array_unique($jb);

        $cj = false;
        $cost = ConfigParam::getInstance()->getFmtParam('SPIRIT_PULL_COST');
        $goodsNum   = $playerSer->getGoods($cost['gid']);
        if($playerSer->getGoods($cost['gid']) >= 5 || $playerSer->getGoods($cost['gid']) >= 1)
        {
            $cj = true;
        }

        return [$list,$groove,$yoke,$cj];
    }

    public function getSpiritAttrAdd(&$attr,array $spirit,&$ratio):void
    {
        //未使用
        if(!$spirit) return ;
        list($detail,$_un) = SpiritService::getInstance()->getSpiritAttrSum($spirit);

        foreach ($detail as $attrName => $attrValue)
        {
            if(array_key_exists($attrName,$attr)){
                $attr[$attrName] = add($attr[$attrName],$attrValue);
            }else{
                $ratio[$attrName] = add($ratio[$attrName],$attrValue);
            }
        }
    }

    //获取精怪技能
    public function getSpiritList($spiritData){

        $squadData = $spiritData['squad'][$spiritData['active']];
        $skillList = array();
        foreach ($squadData as $skillId) {
            $skillList[$skillId] = $spiritData['bag'][$skillId]['lv'];
        }
        return $skillList;

    }

    public function getSpiritSkill($id,$lv){
        //SPIRIT_LEVEL_LIMIT
        $limit  = ConfigParam::getInstance()->getFmtParam('SPIRIT_LEVEL_LIMIT');
        $config = ConfigSpirits::getInstance()->getOne($id);
        if($lv >= $limit){
            $lv = $limit;
        }
        $skillLv = add($lv/5 ,1);
        return[$config['active_skill'] , $skillLv];
    }
}
