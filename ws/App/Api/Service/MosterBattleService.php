<?php

namespace App\Api\Service;

use App\Api\Table\ConfigMonsters;
use App\Api\Table\ConfigMonstersLevel;
use EasySwoole\Component\CoroutineSingleTon;

class MosterBattleService
{
    use CoroutineSingleTon;

    public function battle(array $selfData,string $monsterId,string $monsterLevel,&$selfShowData)
    {
        $enemyAttr = $this->getMonsterAttr($monsterId,$monsterLevel);
        if($enemyAttr['pet']['active'] == -1){
            $active = -1;
        }else{
            $active = $enemyAttr['pet']['bag'][$enemyAttr['pet']['active']]['id'];
        }
        $enemyAdd = ['pet' => [ $active, -1] , 'tactical'=>[],'spirit' =>[]];
        $res =  BattleService::getInstance()->run($selfData,$enemyAttr,15,$selfShowData,$enemyAdd,true);
        $res[] = $enemyAdd;
        return $res;
    }

    public function getMonsterAttr(int $monsterId , int $monsterLevel):array
    {
        $baseAttr   = ConfigMonsters::getInstance()->getOne($monsterId);
        $levelAttr  = ConfigMonstersLevel::getInstance()->getOne($monsterLevel);

        $secAttribute     = div($levelAttr['secAttribute'] , '10000',10);
        $secDefAttribute  = div($levelAttr['secDefAttribute'] , '10000',10);
        $battleAttr = BattleService::getInstance()->getBattleAttrFmt();

        $battleAttr['attack']   =  mul($baseAttr['attackEmp'],$levelAttr['attackBase'] / 1000 );
        $battleAttr['hp']       =  mul($baseAttr['hpEmp'],$levelAttr['hpBase'] / 1000 );
        $battleAttr['defence']  =  mul($baseAttr['defEmp'],$levelAttr['defBase'] / 1000 );
        $battleAttr['speed']    =  mul($baseAttr['speedEmp'],$levelAttr['speedBase'] / 1000 );

        //,'stun'	,'criticalHit'	,'doubleAttack'	,'dodge'	,'attackBack'	,'lifeSteal'
        $battleAttr['stun']            =  mul($baseAttr['stun'],$secAttribute );
        $battleAttr['critical_hit']    =  mul($baseAttr['criticalHit'],$secAttribute );
        $battleAttr['double_attack']   =  mul($baseAttr['doubleAttack'],$secAttribute );
        $battleAttr['dodge']           =  mul($baseAttr['dodge'],$secAttribute );
        $battleAttr['attack_back']     =  mul($baseAttr['attackBack'],$secAttribute );
        $battleAttr['life_steal']      =  mul($baseAttr['lifeSteal'],$secAttribute );

        $battleAttr['re_stun']            =  mul($baseAttr['reStun'],$secDefAttribute );
        $battleAttr['re_critical_hit']    =  mul($baseAttr['reCriticalHit'],$secDefAttribute );
        $battleAttr['re_double_attack']   =  mul($baseAttr['reDoubleAttack'],$secDefAttribute );
        $battleAttr['re_dodge']           =  mul($baseAttr['reDodge'],$secDefAttribute );
        $battleAttr['re_attack_back']     =  mul($baseAttr['reAttackBack'],$secDefAttribute );
        $battleAttr['re_life_steal']      =  mul($baseAttr['reLifeSteal'],$secDefAttribute );
        // "{\"apply\":10007,\"stage\":4,\"lv\":60,\"step\":0,\"list\":[10001,10006,10005,10004,10003,10002,10007]}"
        if($baseAttr['cloud'] && $levelAttr['cloudLevel'])
        {
            BattleService::getInstance()->getCloudAttrAdd($battleAttr,[ 'apply' => $baseAttr['cloud'],'lv' => $levelAttr['cloudLevel'] ],'npc');
        }

        //获取怪物宠物数据 $baseAttr['pet'] 获取到id  $enemy['pet']['bag'][$enemy['pet']['active']]['id']
        if($levelAttr['petLevel'] == 0) {
            $petId = -1;
            $active = -1;
            $petLv = -1;
        }else{
            $petId = $baseAttr['pet'];
            $active = 0;
            $petLv = 1;
        }
       $battleAttr['pet'] = [
           'active' => $active ,
           'bag' => [0=>['id' =>$petId ,'lv'=>$petLv]]
       ];
        //获取怪物精怪数据
        $battleAttr['spirit'] = [
            'active' => 0,
            'squad' => [0 => []],
            'bag' => [],
        ];

        for ($count = $levelAttr['spiritCount']; $count > 0; $count--) {
            $battleAttr['spirit']['squad'][0][] = $baseAttr['spirit'][$count - 1];
            $battleAttr['spirit']['bag'][$baseAttr['spirit'][$count - 1]] = ['lv'=>$levelAttr['spiritLevel']];
        }

        return $battleAttr;
    }

}
