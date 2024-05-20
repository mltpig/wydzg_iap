<?php
namespace App\Api\Service\Module;

use App\Api\Service\BattleService;
use App\Api\Service\PlayerService;
use App\Api\Table\ConfigSystemInfo;
use App\Api\Utils\Consts;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigPets;
use App\Api\Table\ConfigSkill;
use App\Api\Table\ConfigCombine;
use App\Api\Table\ConfigSkillRandom;
use EasySwoole\Component\CoroutineSingleTon;

class PetService
{
    use CoroutineSingleTon;

    public function initPet(PlayerService $playerSer):void
    {
        //解锁初始化
        if($pet = $playerSer->getData('pet')) return ;
        $bagSize = ConfigParam::getInstance()->getFmtParam('PET_BAG_SIZE');
        $bag = [];
        for ($i=0; $i < $bagSize; $i++)
        {
            $bag[] = [];
        }

        $bag[0] = $this->getBagPetInitFmtData(111002,1);

        $pet = [
            'active' => -1,
            'bag'    => $bag,
            'map'    => [111002 => 1],
            'pool'   => [[ 'id' => 111002 , 'state' => 0 ],[ 'id' => 111002 , 'state' => 0 ],[ 'id' => 111002 , 'state' => 0 ]]
            // 'pool'   => $this->getPetRandom(3)
        ];

        $playerSer->setPet('',0,$pet,'flushall');
    }

    public function getBagPetInitFmtData(int $petid,int $skillNum):array
    {
        return [ 'id' => $petid,'lv' => 1,'star' => 0,'lock' => 0,'skill' => $this->getSkillRandom($skillNum,[]),'help' => -1 ];
    }

    public function getSkillRandom(int $num,array $filter):array
    {

        $skill = [];

        list($skills,$weight ) = ConfigSkillRandom::getInstance()->getAllWeight(2,$filter);
        for ($i=0; $i < $num; $i++)
        {
            $index = randTable($weight);
            $skill[ $skills[$index] ] = 1 ;
            unset($weight[$index],$skills[$index]);
        }
        return $skill;
    }

    public function dailyReset(PlayerService $playerSer):void
    {
        //重置每日广告次数
        $playerSer->setArg(Consts::PET_AD_TAG,1,'unset');
    }

    public function getPetRandom(int $size):array
    {
        $list = [];
        $pools = ConfigPets::getInstance()->getAllWeight();

        for ($i=0; $i < $size; $i++)
        {
            $list[] = [ 'id' => randTable($pools) , 'state' => 0 ];
        }

        return $list;
    }

    public function checkFreeBag(array $bag):int
    {
        $bagid = -1;
        foreach ($bag as $key => $value)
        {
            if(!$value) return $key;
        }
        return $bagid;
    }

    public function getUpLvCost(int $lv):int
    {
        $map = ConfigParam::getInstance()->getFmtParam('PET_LEVELUP_COST_PARAM');
        return $map[ $lv - 1 ] ;
    }

    public function getUpLvTotalCost(int $lv,int $lvCostNum):int
    {
        $sum = 0;
        $map = ConfigParam::getInstance()->getFmtParam('PET_LEVELUP_COST_PARAM');
        foreach ($map as $key => $value)
        {
            if($key >= $lv ) return $sum;
            $sum += ceil($lvCostNum * ($value/1000));
        }

        return $sum;
    }

    //羁绊加成
    public function getPetLineupAdd(array $map):array
    {
        $skills = [];
        $config = ConfigPets::getInstance()->getAllCombineId();
        foreach ($map as $petid => $_val)
        {
            foreach ($config as $skillid => $petids)
            {
                if(!array_key_exists($petid,$petids)) continue;
                unset($config[$skillid][$petid]);
                if($config[$skillid]) continue;
                $combineConfig = ConfigCombine::getInstance()->getOne($skillid);
                if(!$combineConfig) continue;
                $skills[$combineConfig['comb_skill_id']] = 1;
            }
        }

        return $skills;
    }

    public function getPetFmtData(PlayerService $playerSer):array
    {
        $petData = $playerSer->getData('pet');
        $adCount = $playerSer->getArg( Consts::PET_AD_TAG );

        $nowSize  = count($petData['bag']);
        $count    = $nowSize - ConfigParam::getInstance()->getFmtParam('PET_BAG_SIZE');
        $bagCost  = ConfigParam::getInstance()->getFmtParam('PET_BAG_ADD_COST');
        $cost     = array_key_exists($count,$bagCost) ? $bagCost[$count] :  ['gid' => '100000','num'=>'99999999'];;
        $cost['type'] = GOODS_TYPE_1;
        $bag = [
            'active'        => $petData['active'],
            'list'          => [],
            'unlockNum'     => $nowSize,
            'unlockBoxCost' => $cost,
        ];

        foreach ($petData['bag'] as $index => $value)
        {
            if(!$value) continue;
            $value['index'] = $index;
            $bag['list'][] = $value;
        }
        $petData['bag'] = $bag;
        unset($petData['active']);

        $refreshPetCost =  ConfigParam::getInstance()->getFmtParam('PET_REFRESH_COST');
        $refreshPetCost['type'] = GOODS_TYPE_1;

        $freeCount = ConfigParam::getInstance()->getFmtParam('PET_FREE_REFRESH_TIME');
        $petData['config'] = [
            'adRefreshPetLimit' => ConfigParam::getInstance()->getFmtParam('PET_FREE_REFRESH_TIME') + 0,
            'refreshPetCost'    => $refreshPetCost
        ];

        $petData['adRefreshPet'] = $freeCount - $adCount;

        $config_refresh = ConfigParam::getInstance()->getFmtParam('PET_DRAW_SPECIFIC_PROTECT_PARAM') + 0;
        $petData['refresh_count']   = $config_refresh - $playerSer->getArg( Consts::PET_REFRESH_COUNT );
        $petData['mg_count']        = $playerSer->getArg( Consts::PET_MG_COUNT );

        $petData['wish']            = array_key_exists('wish',$petData) ? $petData['wish'] : 0;

        return $petData;
    }

    public function getUpSkillId(array $skills):int
    {
        $pool = [];
        foreach ($skills as $skillid => $lv)
        {
            $skillConf = ConfigSkill::getInstance()->getOne($skillid);
            if($lv >= $skillConf['maxLevel'] ) continue;
            $pool[$skillid] = 1;
        }
        if(!$pool) return 0;

        return array_rand($pool,1);
    }

    public function getPetAdd(&$ratio,array $pet):void
    {
        if(!$pet) return;
        //图鉴
        $mapSkill    = $this->getPetLineupAdd($pet['map']);
        $this->getPetSkillAdd($ratio,$mapSkill);
        //上阵灵兽被动技能
        if($pet['active'] == -1 ) return;
        $this->getPetSkillAdd($ratio,$pet['bag'][$pet['active']]['skill']);
        $this->getPetAttrAdd($ratio,$pet['bag'][$pet['active']]);
    }

    public function getPetSkillAdd(&$ratio,array $skills):void
    {
        $skillTypeMap = BattleService::getInstance()->getSkillTypeMap();
        //普通技能
        foreach ( $skills as $skillId => $level )
        {
            $skillConfig = ConfigSkill::getInstance()->getOne($skillId);
            foreach ($skillConfig['type'] as $index => $type )
            {
                //不在列表。暂不统计
                if(!array_key_exists($type,$skillTypeMap)) continue;

                $skillLvVal = $skillConfig['params'][$index][0] + $skillConfig['upgradeParams'][$index][0] * ( $level - 1 );

                $ratio[ $skillTypeMap[$type ]] = add( $ratio[ $skillTypeMap[$type] ] , $skillLvVal);
            }
        }
    }

    public function getPetGoIds(array $petData):array
    {
        // 没解锁灵宠
        if(empty($petData)) return ['active' => -1, 'help' => -1];

        // 是否上阵
        $activeValue = $petData['active'];
        if($activeValue < 0) return ['active' => -1, 'help' => -1];

        // 上阵灵宠
        $pets = $petData['bag'][$activeValue];
        $active = $pets['id'];

        // 上阵协战副灵宠
        $help = $pets['help'] < 0 ? $pets['help'] : $petData['bag'][$pets['help']]['id'];

        return [ $active, $help];
    }

    public function getPetAttrAdd(&$ratio,array $petDetail):void
    {
        $id   = $petDetail['id'];
        $lv   = $petDetail['lv'] - 1;//只计算升了的级数，默认为1
        $star = $petDetail['star'];
        $config = ConfigPets::getInstance()->getOne($id);
        //基础属性百分比值
        $ratio['ratio_hp']       = add($ratio['ratio_hp'],$config['hp_basic']/10);
        $ratio['ratio_attack']   = add($ratio['ratio_attack'],$config['attack_basic']/10);
        $ratio['ratio_defence']  = add($ratio['ratio_defence'],$config['defense_basic']/10);
        //等级加成
        $ratio['ratio_hp']       = add($ratio['ratio_hp'],$lv * $config['hp_add']/10);
        $ratio['ratio_attack']   = add($ratio['ratio_attack'],$lv * $config['attack_add']/10);
        $ratio['ratio_defence']  = add($ratio['ratio_defence'],$lv * $config['defense_add']/10);
        //星级加成
        $ratio['ratio_hp']       = add($ratio['ratio_hp'],$star * $config['hp_star_add']/10);
        $ratio['ratio_attack']   = add($ratio['ratio_attack'],$star * $config['attack_star_add']/10);
        $ratio['ratio_defence']  = add($ratio['ratio_defence'],$star * $config['defense_star_add']/10);
    }

    public function getPetRedPointInfo(PlayerService $playerSer):array
    {
        $petActiveUpLv = false;
        //上阵副将是否可升级
        $pet = $playerSer->getData('pet');
        if( $pet['active'] != -1 )
        {
            $detail = $pet['bag'][$pet['active']];
            $config = ConfigPets::getInstance()->getOne( $detail['id'] );

            if($config['level_limit'] > $detail['lv'])
            {
                $cost    = $config['level_cost'];
                $costNum = ceil( $cost['num'] * PetService::getInstance()->getUpLvCost($detail['lv'])/1000 );

                $petActiveUpLv = $playerSer->getGoods($cost['gid']) >= $costNum;
            }
        }

        //是否有广告刷新
        $freeCount  = ConfigParam::getInstance()->getFmtParam('PET_FREE_REFRESH_TIME');

        return [ $petActiveUpLv , $freeCount > $playerSer->getArg( Consts::PET_AD_TAG ) ];
    }

    public function getPetActiveSkillAttr(int $petId ,int $petLv):array
    {
        $petConf   = ConfigPets::getInstance()->getOne($petId);
        $skillConf = ConfigSkill::getInstance()->getOne($petConf['active_skill']);
        $skillLvConf = ConfigParam::getInstance()->getFmtParam('PET_ACTIVE_SKILL_UPGRADE');
        $lv = 0;
        foreach ($skillLvConf as $item){
            if($item > $petLv){
                break;
            } else{
                $lv++;
            }
        }
        return [
            'id'=> $petConf['active_skill'],
            'a' => $skillConf['params'][0][0] + $lv * $skillConf['upgradeParams'][0][0],
            'b' => $skillConf['params'][0][1] + $lv * $skillConf['upgradeParams'][0][1],
            'c' => $skillConf['params'][0][2] + $lv * $skillConf['upgradeParams'][0][2],
            'd' => $skillConf['params'][0][3] + $lv * $skillConf['upgradeParams'][0][3],
        ];

    }

    public function getPetMgRandom(PlayerService $playerSer):array
    {
        $pet = $playerSer->getData('pet');

        if($playerSer->getArg(Consts::LIFETTIME_CARD_TIME)) $playerSer->setArg(Consts::PET_REFRESH_COUNT,1,'add'); //TODO 终身卡权益

        $config_refresh = ConfigParam::getInstance()->getFmtParam('PET_DRAW_SPECIFIC_PROTECT_PARAM') + 0;//宠物保底需要次数
        $config_mg      = ConfigParam::getInstance()->getFmtParam('PET_DRAW_PROTECT_PARAM') + 0;//宠物掉落保护

        $pet_refresh_count  = $playerSer->getArg( Consts::PET_REFRESH_COUNT );
        $pet_mg_count       = $playerSer->getArg( Consts::PET_MG_COUNT );

        $pools  = ConfigPets::getInstance()->getQualityWeight(5);

        //刷新N次必出神话灵兽
        $refresh            = $pet_refresh_count % $config_refresh;
        if(empty($refresh) && !empty($pet_refresh_count) && $playerSer->getArg(Consts::LIFETTIME_CARD_TIME)) //TODO 终身卡权益
        {
            //有心愿单
            $list   = $this->getPetRandom(2);
            if(array_key_exists('wish',$pet))
            {
                //神话灵兽掉落保护
                if($pet_mg_count == $config_mg && !empty($pet_mg_count))
                {
                    $list[] = [ 'id' => $pet['wish'] , 'state' => 0 ];
                    $playerSer->setArg(Consts::PET_MG_COUNT,0,'unset');
                }else{
                    $list[] = [ 'id' => randTable($pools) , 'state' => 0 ];
                    $playerSer->setArg(Consts::PET_MG_COUNT,1,'add');
                    if($this->wishWhere($list,$pet['wish'])) $playerSer->setArg(Consts::PET_MG_COUNT,0,'unset');
                }
            }else{
                $list[] = [ 'id' => randTable($pools) , 'state' => 0 ];
            }
            $playerSer->setArg(Consts::PET_REFRESH_COUNT,0,'unset');
        }else{
            if(array_key_exists('wish',$pet))
            {
                //神话灵兽掉落保护
                if($pet_mg_count == $config_mg && !empty($pet_mg_count))
                {
                    $where   = $this->getPetRandom(3);
                    if($this->randomWhere($where,$pools)){ //抽出神话灵兽将替换成心愿灵兽
                        $list   = $this->getPetRandom(2);
                        $list[] = [ 'id' => $pet['wish'] , 'state' => 0 ];
                        $playerSer->setArg(Consts::PET_MG_COUNT,0,'unset');
                    }else{
                        $list   = $where;
                    }
                }else{
                    $list   = $this->getPetRandom(3);
                    if($this->randomWhere($list,$pools)) $playerSer->setArg(Consts::PET_MG_COUNT,1,'add');
                    if($this->wishWhere($list,$pet['wish'])) $playerSer->setArg(Consts::PET_MG_COUNT,0,'unset');
                }
            }else{
                $list   = $this->getPetRandom(3);
            }
        }

        return $list;
    }

    public function randomWhere(array $list, array $pools)
    {
        $where = 0;
        foreach($list as $k => $v)
        {
            if(!array_key_exists($v['id'],$pools)) continue;
            $where = 1;
        }
        return $where;
    }

    public function wishWhere($list, $wish)
    {
        $where = 0;
        foreach($list as $k => $v)
        {
            if($v['id'] != $wish) continue;
            $where = 1;
        }
        return $where;
    }

    /**
     * 判断是否需要初始化副将头像
     * @param PlayerService $playerSer
     * @return void
     * @throws \Exception
     */
    public function initPetHead(PlayerService $playerSer):void
    {
        //如果已经重置过头像，不处理
        if($playerSer->getArg(Consts::UPDATE_PET_HEAD) > 0){
            return;
        }

        //解锁初始化，证明没有副将
        $pet = $playerSer->getData('pet');
        if(!$pet) {
            $playerSer->setArg(Consts::UPDATE_PET_HEAD,1,'add');
            return ;
        }
        $headInfo = $playerSer->getData('head');
        $headInfo[5] = array();
        foreach ($pet['map'] as $key => $value){
            $config = ConfigPets::getInstance()->getOne($key);
            $headInfo[5][]= $config['icon'];
        }
        $playerSer->setData('head',null,$headInfo);


        $playerSer->setArg(Consts::UPDATE_PET_HEAD,1,'add');

    }


}
