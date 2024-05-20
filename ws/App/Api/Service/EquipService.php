<?php
namespace App\Api\Service;
use App\Api\Table\ConfigRole;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigTree;
use App\Api\Table\ConfigEquipBase;
use App\Api\Table\ConfigEquipAttach;
use App\Api\Table\ConfigEquipSpecial;
use EasySwoole\Component\CoroutineSingleTon;

class EquipService
{
    use CoroutineSingleTon;

    public function extract(int $treeLv,int $roleLv,array $option,int $isRobot = 0):array
    {
        $roleConfig  = ConfigRole::getInstance()->getOne($roleLv);
        $treeConfig  = ConfigTree::getInstance()->getOne($treeLv);

        $level   = randTable($roleConfig['equipment_level']);
        $type    = $isRobot > 0 ? $isRobot : randTable($roleConfig['equipment_type']);
        $quality = randTable($treeConfig['quality']);
        
        $baseConfig    = ConfigEquipBase::getInstance()->getOneByLevel($level);

        $attachConfig  = ConfigEquipAttach::getInstance()->getOne($type,$quality);
        //装备基础属性随机规则
        //装备攻击=掉落等级攻击的基础值*攻击比例随机值/1000*装备精炼的加成值
        $attack =   $this->getBaseAttrVal($baseConfig['attack'],$attachConfig['prim_attack']);
        //装备血量=掉落等级血量的基础值*血量比例随机值/1000*装备精炼的加成值
        $hp =   $this->getBaseAttrVal($baseConfig['hp'],$attachConfig['prim_hp']);
        //装备防御=掉落等级防御的基础值*防御比例随机值/1000*装备精炼的加成值
        $defence =   $this->getBaseAttrVal($baseConfig['defence'],$attachConfig['prim_defence']);
        //装备速度=掉落等级速度的基础值*速度比例随机值/1000*装备精炼的加成值
        $speed =   $this->getBaseAttrVal($baseConfig['speed'],$attachConfig['speed']);

        //装备副词条	当前装备稀有度≥3时为装备添加第一副词条：击晕、暴击、连击、闪避、反击、吸血
        if($option)
        {
            $sec_attribute_ran = $attachConfig['sec_attribute_ran'];
            $sum   =  array_sum($sec_attribute_ran);
            $other =  0;
            foreach ($sec_attribute_ran as $attrType => $weight) 
            {
                if(array_key_exists($attrType,$option)) continue;
                $other +=  $weight;
            }
            foreach ($option as $attrType => $weight) 
            {
                if(!$weight) continue;
                if(!$sum) continue;
                $ratio =  ($attachConfig['sec_attribute_ran'][$attrType] / $sum) * ( (1000 + $weight)/1000 ) ;
                $attachConfig['sec_attribute_ran'][$attrType] = ceil( $other * $ratio / (1 - $ratio * count($option) ) );
            }
        }

        $secExists = $secDefExists   = [];
        if($attachConfig['sec_attribute_ran_num'] > 0 )
        {
            $attr     = [ 1  => 'stun',2  => 'critical_hit',3  => 'double_attack',4  => 'dodge',5  => 'attack_back',6  => 'life_steal'];
            $randList = $attachConfig['sec_attribute_ran'];
            for ($i = 0; $i < $attachConfig['sec_attribute_ran_num']; $i++) 
            { 
                $attrType =  randTable($randList);
                $secAttribute = $attachConfig[$attr[$attrType]]; 
                if(!array_sum($secAttribute)) continue;
                //第一副词条属性 = 当前稀有度副词条的基础属性的范围/属性比例
                $secExists[ $attrType ] = div( rand($secAttribute[0],$secAttribute[1])*1000 , $baseConfig['special']);
                unset($randList[$attrType]);
            }
        }

        //当前装备稀有度≥6时为装备添加第二副词条：抗击晕、抗暴击、抗连击、抗闪避、抗反击、抗吸血
        //第二副词条属性=当前稀有度副词条的基础属性的范围/属性比例
        if($attachConfig['sec_def_attribute_ran_num'] > 0 )
        {
            $attr     = [ 1  => 're_stun',2  => 're_critical_hit',3  => 're_double_attack',4  => 're_dodge',5  => 're_attack_back',6  => 're_life_steal'];
            $randList = $attachConfig['sec_def_attribute_ran'];
            for ($i=0; $i < $attachConfig['sec_def_attribute_ran_num']; $i++) 
            { 
                $attrType =  randTable($randList);
                $secAttribute = $attachConfig[$attr[$attrType]]; 
                if(!array_sum($secAttribute)) continue;
                $secDefExists[$attrType] = div( rand($secAttribute[0],$secAttribute[1])*1000 , $baseConfig['special_def']);
                unset($randList[$attrType]);
            }
        }

        return [ 
            'equipid' => $baseConfig['id'],
            'lv'      => $level,
            'type'    => $type,
            'quality' => $quality,
            'base'    => [
                'attack'  => $attack,
                'hp'      => $hp,
                'defence' => $defence,
                'speed'   => $speed,
            ],
            'sec_attr'     => $secExists,
            'sec_def_attr' => $secDefExists,
            'name' => $attachConfig['name'],
            'icon' => $attachConfig['icon'],
        ];
    }

    public function getBaseAttrVal(string $base,array $arg,$num2 = '1000'):string
    {
        $attackRatio = div( rand($arg[0],$arg[1]) , $num2,6);
        return mul($base,$attackRatio);
    }

    public function getGuideExtract(int $counter,int $equipid = null ):array
    {
        if(is_null($equipid))
        {
            $guideEquip = ConfigParam::getInstance()->getFmtParam('EQUIPMENT_SPECIAL_DROP_LIST');
            $equipid = $guideEquip[$counter];
        }
        
        $specialConfig  = ConfigEquipSpecial::getInstance()->getOne($equipid);
        $level          = $specialConfig['level'];
        $baseConfig     = ConfigEquipBase::getInstance()->getOneByLevel($level);

        $attrList   = [ 1  => 'stun',2  => 'critical_hit',3  => 'double_attack',4  => 'dodge',5  => 'attack_back',6  => 'life_steal'];
        $secExists  = $this->getSpecialAttr($attrList,$specialConfig,$baseConfig['special']);

        $attrList     = [ 1  => 're_stun',2  => 're_critical_hit',3  => 're_double_attack',4  => 're_dodge',5  => 're_attack_back',6  => 're_life_steal'];
        $secDefExists = $this->getSpecialAttr($attrList,$specialConfig,$baseConfig['special_def']);

        return [ 
            'equipid' => $specialConfig['id'],
            'lv'      => $level,
            'type'    => $specialConfig['type'],
            'quality' => $specialConfig['quality'] ,
            'base'    => [
                'attack'  => $specialConfig['prim_attack'][0],
                'hp'      => $specialConfig['prim_hp'][0],
                'defence' => $specialConfig['prim_defence'][0],
                'speed'   => $specialConfig['prim_speed'][0],
            ],
            'sec_attr'     => $secExists,
            'sec_def_attr' => $secDefExists,
            'name' => $specialConfig['name'],
            'icon' => $specialConfig['icon'],
        ];
    }

    public function getSpecialAttr(array $attrList,array $config,int $num2 ):array
    {
        $attrs = [];
        foreach ($attrList as $attrType => $value) 
        {
            $secAttribute = $config[$value]; 
            if(!array_sum($secAttribute)) continue;
            $attrs[ $attrType ] = div( rand($secAttribute[0],$secAttribute[1])*1000 , $num2);
        }
        return $attrs;
    }

    public function getEquipQualityCount(array $equip,int $quality):int
    {
        $count = 0;
        foreach ($equip as $key => $value) 
        {
            if(!$value || $value['quality'] < $quality) continue;
            $count += 1;
        }
        return $count;
    }

    public function getEquipFmtData(array $list):array
    {
        $data = [];
        foreach ($list as $key => $value) 
        {
            if($value)
            {
                $value['lv']        = strval($value['lv']);
                $value['type']      = strval($value['type']);
                $value['quality']   = strval($value['quality']);
            }
            $data[$key] = $value;
        }
        return $data;
    }

    public function getEquipAttrAdd(&$attr,array $equip):void
    {
        if(!$equip) return;
        $secAttr = [ 1  => 'stun',2  => 'critical_hit',3  => 'double_attack',4  => 'dodge',5  => 'attack_back',6  => 'life_steal'];
        $secDefAttr = [ 1  => 're_stun',2  => 're_critical_hit',3  => 're_double_attack',4  => 're_dodge',5  => 're_attack_back',6  => 're_life_steal'];

        foreach ($equip as $pos => $detail) 
        {
            if(!$detail) continue;

            foreach ($detail['base'] as $baseAttrName => $baseVal) 
            {
                $attr[$baseAttrName]     = add($attr[$baseAttrName],$baseVal);
            }

            foreach ($detail['sec_attr'] as $secKey => $secVal) 
            {
                $secAttrName = $secAttr[$secKey];
                $attr[$secAttrName]      = add($attr[$secAttrName],$secVal);
            }

            foreach ($detail['sec_def_attr'] as $secDefKey => $secDefVal) 
            {
                $secDefAttrName = $secDefAttr[$secDefKey];
                $attr[$secDefAttrName]    = add($attr[$secDefAttrName],$secDefVal);
            }
        }

    }

}
