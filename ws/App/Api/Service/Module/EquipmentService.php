<?php
namespace App\Api\Service\Module;

use App\Api\Service\BattleService;
use App\Api\Service\PlayerService;
use App\Api\Utils\Consts;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigSkill;
use App\Api\Table\ConfigEquipmentAdvanceUp;
use App\Api\Table\ConfigEquipmentAdvance;
use EasySwoole\Component\CoroutineSingleTon;

class EquipmentService
{
    use CoroutineSingleTon;

    public function initEquipment(PlayerService $playerSer):void
    {
        //解锁初始化
        if($equipment = $playerSer->getData('equipment')) return ;

        // 1=武器   2=头部    3=衣服      4=鞋子 
        // 5=项链   6=腰带    7=护腕      8=宝珠
        // 9=法器   10=秘宝   11=信物     12=傀儡
        $equipment = [
            'stage'         => 1,
            'equiplv'       => [
                    1 => 0,
                    2 => 0,
                    3 => 0,
                    4 => 0,
                    5 => 0,
                    6 => 0,
                    7 => 0,
                    8 => 0,
                    9 => 0,
                    10 => 0,
                    11 => 0,
                    12 => 0,
            ],
        ];
        
        $equipment['hm'] = ConfigEquipmentAdvanceUp::getInstance()->getHmAll();


        $playerSer->setEquipment('',0,$equipment,'flushall');
    }

    public function dailyReset(PlayerService $playerSer):void
    {

    }

    public function getEquipmentFmtData(PlayerService $playerSer):array
    {
        $equipmentData = $playerSer->getData('equipment');

        return $equipmentData;
    }

    public function getUpLvEquipType(array $equip):array
    {
        $max    = max($equip);
        $keys   = array_keys($equip, $max);

        $bw = $keys[count($keys) - 1];
        
        // 最后一件部位 && 等级上限11090
        if($bw == 12 && $max == 11090)
        {
            $lv = $type = 0;

        }else if($bw == 12 && $max < 11090){

            $lv     = $max + 1;
            $type   = 1;

        }else{

            $lv     = $max;
            $type   = $bw + 1;
        }

        return [$lv, $type];
    }

    public function getEquipmentLevelLimit($equip, $level):int
    {
        $where = 0;
        foreach($equip as $k => $v)
        {
            if($v < $level) $where = 1;
        }

        return $where;
    }

    public function getEquipmentWhereUpStage($equip, $level):int
    {
        $where = 0;
        if($this->allValuesEqualTo($equip, $level))
        {
            $where = 1;
        }

        return $where;
    }

    function allValuesEqualTo($array, $value) {
        return count(array_filter($array, function($item) use ($value) {
            return $item == $value;
        })) == count($array);
    }

    public function getEquipmentSkillSum(array $equipment):array
    {
        // 暂时不做等迭代 合鸣技能属性
        $map = BattleService::getInstance()->getSkillTypeMap();
        $sum = BattleService::getInstance()->getAttrRatioFmt();
        
        $skill = [];
        foreach($equipment['hm'] as $id => $state)
        {
            if($state)
            {
                $config = ConfigEquipmentAdvanceUp::getInstance()->getOne($id);
                $skill[] = $config['special_skill'];
            }
        }

        // 合鸣技能加成
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

    public function getEquipmentAttrAdd(&$attr,array $equip,array $equipment,&$ratio):void
    {
        if(!$equip || !$equipment) return;
        
        // 精炼所有加成
        foreach ($equip as $pos => $detail)
        {
            if(!$detail) continue;

            foreach ($detail['base'] as $baseAttrName => $baseVal) 
            {
                if($baseAttrName == "speed") continue;
                
                $hz_value = $bz_value = 0;
                
                // 百分比黄字属性
                $hz_config  = ConfigEquipmentAdvanceUp::getInstance()->getOne($equipment['stage']);
                $hz_value   = $this->getStageAttrVal($baseVal,$hz_config['equipment_up']);
      
                // 固定白字属性
                $lv = $equipment['equiplv'][$detail['type']];
                if($lv)
                {
                    $bz_config = ConfigEquipmentAdvance::getInstance()->getOne($lv);
                    if(array_key_exists($baseAttrName,$bz_config)){
                        $bz_value = mul($bz_config[$baseAttrName],$lv);
                    }
                }
                
                $value = add($hz_value,$bz_value);

                $attr[$baseAttrName]     = add($attr[$baseAttrName],$value);
            }
        }

        // 合鸣所有加成
        $sum = $this->getEquipmentSkillSum($equipment);
        foreach ($sum as $attrName => $attrValue)
        {
            $ratio[$attrName] = add($ratio[$attrName],$attrValue);
        }
    }

    public function getStageAttrVal(string $base,$arg,$num2 = '1000'):string
    {
        $ratio = div($arg, $num2, 6);
        return mul($base,$ratio);
    }

    public function getEquipmentRedPointInfo(PlayerService $playerSer)
    {
        $equipment = $playerSer->getData('equipment');

        $max_stage = 41; // 最高等阶
        $max_stage_config  = ConfigEquipmentAdvanceUp::getInstance()->getOne($max_stage);
        $max_lv = true;
        foreach($equipment['equiplv'] as $type => $lv)
        {
            if($lv < $max_stage_config['level_limit'])
            {
                $max_lv = false;
            }
        }

        $up_red = false;
        $hm_red = false;

        if($equipment['stage'] == $max_stage && $max_lv)
        {
            $up_red = false;
        }else{
            $stage_config   = ConfigEquipmentAdvanceUp::getInstance()->getOne($equipment['stage']);
            $up_required    = ConfigEquipmentAdvance::getInstance()->getOne(1); //1级消耗
            foreach($equipment['equiplv'] as $type => $lv)
            {
                $cost        = $up_required['cost'];
                if($lv < $stage_config['level_limit'] && $playerSer->getGoods($cost[0]['gid']) >= $cost[0]['num'] && $playerSer->getGoods($cost[1]['gid']) >= $cost[1]['num'])
                {
                    $up_red = true;
                }
            }

            if($equipment['stage'] < $max_stage && $this->getEquipmentWhereUpStage($equipment['equiplv'],$stage_config['level_limit']))
            {
                $big_cost = $stage_config['big_cost'];
                if($playerSer->getGoods($big_cost[0]['gid']) >= $big_cost[0]['num'] && $playerSer->getGoods($big_cost[1]['gid']) >= $big_cost[1]['num'])
                {
                    $up_red = true;
                }
            }
        }

        foreach($equipment['hm'] as $index => $val)
        {
            if($index <= $equipment['stage'] && $val == 0)
            {
                $hm_red = true;
            }
        }


        return [$up_red,$hm_red];
    }
}
