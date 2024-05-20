<?php
namespace App\Api\Service;

use App\Api\Utils\Consts;
use App\Api\Table\ConfigMonsters;
use App\Api\Table\ConfigRole;
use App\Api\Table\ConfigSkill;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigComrade;
use App\Api\Table\ConfigComradeVisit;
use App\Api\Table\ConfigMonstersLevel;
use App\Api\Table\ConfigTask;
use EasySwoole\Component\CoroutineSingleTon;

class ComradeService
{
    use CoroutineSingleTon;

    public function initComrade(PlayerService $playerSer):void
    {
        //解锁初始化
        if($playerSer->getData('comrade')) return ;
        $adminTask = TaskService::getInstance()->getAdminTask($playerSer->getData('task'),1);
        //主线任务判断,主线只有一个
        if(!$adminTask || $adminTask < 100064) return;

        $initId  = ConfigComrade::getInstance()->getInitId();
        $initFmt = $this->getInitFmtData();
        $initFmt['state'] = 1;
        $initFmt['lv']    = 1;
        $playerSer->setComrade($initId,null,$initFmt,'set');

        //体力初始化
        $roleConfig = ConfigRole::getInstance()->getOne($playerSer->getData('role','lv'));
        $playerSer->setArg(Consts::COMRADE_ENERGY,$roleConfig['destiny_energy'],'reset');
    }

    public function dailyReset(PlayerService $playerSer):void
    {
        //游历每日广告刷新次数
        $playerSer->setArg(Consts::COMRADE_AD_COUNT,1,'unset'); 
    }

    public function check(PlayerService $playerSer,int $time):void
    {
        //无需恢复体力
        if( !$begin = $playerSer->getArg(Consts::COMRADE_ENERGY_TIME) ) return ;
        $ttl = ConfigParam::getInstance()->getFmtParam('DESTINY_ENERGY_TIME');
        //不足一个周期
        $num = floor( ($time - $begin) / $ttl);
        if(!$num ) return;
        $max = ConfigRole::getInstance()->getOne($playerSer->getData('role','lv'))['destiny_energy'];
        $now = $playerSer->getArg(Consts::COMRADE_ENERGY);
        if( ($now + $num) > $max )
        {
            $playerSer->setArg(Consts::COMRADE_ENERGY,$max,'reset');
            $playerSer->setArg(Consts::COMRADE_ENERGY_TIME,1,'unset');
        }else{
            $playerSer->setArg(Consts::COMRADE_ENERGY,$now + $num,'reset');
            $playerSer->setArg(Consts::COMRADE_ENERGY_TIME, $begin + $ttl * $num,'reset');
        }
    }

    //回收时碎片判断
    public function gcCheck(PlayerService $playerSer):void
    {
        //任务激活后初始化
        $this->initComrade($playerSer);
        //普通贤士激活判断
        $this->checkComradeStatue($playerSer);

        $initFmt = $this->getInitFmtData();
        $comrade = $playerSer->getData('comrade');
        $all     = ConfigComrade::getInstance()->getAll();
        foreach ($all as $id => $value) 
        {
            if(!$value['cost_id']) continue;
            if(array_key_exists($id,$comrade)) continue;
            if($playerSer->getGoods($value['cost_id']['gid']) < $value['cost_id']['num'] ) continue;
            $playerSer->setComrade($id,null,$initFmt,'set');
        }
    }

    public function checkComradeStatue(PlayerService $playerSer):void
    {
        //
        $comrade = $playerSer->getData('comrade');
        $initId  = ConfigComrade::getInstance()->getInitId();
        if(!$comrade || !array_key_exists($initId,$comrade)) return ;

        $all = ConfigComrade::getInstance()->getAll();
        foreach ($all as $id => $value) 
        {
            if(!$value['quest_id']) continue;
            if(array_key_exists($id,$comrade)) continue;

            $taskConfig = ConfigTask::getInstance()->getOne($value['quest_id']);
            list($num,$state) = TaskService::getInstance()->getTaskState($playerSer,0,$taskConfig);
            if(!$state) continue;
            $initFmt = $this->getInitFmtData();
            $playerSer->setComrade($id,null,$initFmt,'set');
        }

    }

    public function getInitFmtData():array
    {
        return [ 'lv' => 0,'step' => 0,'battle' => 0,'state' => 0 ];
    }
    
    public function getNeedGoods(PlayerService $player):array
    {
        return [
            Consts::QINPU      => $player->getGoods(Consts::QINPU),
            Consts::DUKANGJIU  => $player->getGoods(Consts::DUKANGJIU),
            Consts::WANSHOUTAO => $player->getGoods(Consts::WANSHOUTAO),
        ];
    }

    public function getComradeAttrSum(array $comrades ):array
    {
        // admin:
        // 以下全为百分比
        // 1001=攻击 1002=生命 1003=防御 1004=敏捷 
        // 1005=击晕 1006=暴击 1007=连击 1008=闪避 1009=反击 1010=吸血 
        // 1011=抗击晕 1012=抗暴击 1013=抗连击 1014=抗闪避 1015=抗反击 1016=抗吸血 
        // 1017=最终增伤 1018=最终减伤 1019=强化爆伤 1020=弱化爆伤 1021=强化治疗 1025=

        // 6001：灵兽吞噬或者放生时返还8%御灵石
        // 6002：灵脉分解获得材料提升10%
        // 6004：自动锤炼时获得指向属性概率提升8%
        // 6005：斗法获胜时，有8%概率额外获得一个庚金
        
        // 以下为实际数值（非百分比）
        // 6003：福地鼠宝充沛状态的体力提升10
        // 6006：挑战妖王速战，掉落仙桃数提升20
        
        $map = BattleService::getInstance()->getSkillTypeMap();
        $sum = BattleService::getInstance()->getAttrRatioFmt();

        $sumId = [
            1001 => '0',1002 => '0',1003 => '0',1004 => '0',1005 => '0',1006 => '0',
            1007 => '0',1008 => '0',1009 => '0',1010 => '0',1011 => '0',1012 => '0',
            1013 => '0',1014 => '0',1015 => '0',1016 => '0',1017 => '0',1018 => '0',
            1019 => '0',1020 => '0',1021 => '0',2001 => '0',2002 => '0',2003 => '0',
            2004 => '0',
        ];

        foreach ($comrades as $comradesid => $detail) 
        {
            if(!$detail['state']) continue;

            $comradeConfig = ConfigComrade::getInstance()->getOne($comradesid);

            $skills = array_slice($comradeConfig['skill_list'],0,$detail['lv']);
            //普通技能
            foreach ($skills as $skillId) 
            {
                $skillConfig = ConfigSkill::getInstance()->getOne($skillId);
                foreach ($skillConfig['type'] as $index => $type)
                {
                    //不在列表。暂不统计
                    if(!array_key_exists($type,$map)) continue;
                    $sum[ $map[$type ]] = add( $sum[ $map[$type] ] ,$skillConfig['params'][$index][0]);
                    $sumId[ $type ] = add( $sumId[ $type ] ,$skillConfig['params'][$index][0]);
                }
            }

            //战斗天赋 / 天赋
            foreach ([$comradeConfig['battle_talent'],$comradeConfig['talent']] as $talentSkill) 
            // foreach ([$comradeConfig['battle_talent']] as $talentSkill) 
            {
                //战斗天赋
                $skillConfig  = ConfigSkill::getInstance()->getOne($talentSkill);
                foreach ($skillConfig['type'] as $index =>  $type)
                {
                    //不在列表。暂不统计
                    if(!array_key_exists($type,$map)) continue;
                    $stage  = $this->getLvStage($detail['lv'],$comradeConfig['talent_level_up']);
                    $addNum = $stage > 0 ? $skillConfig['params'][$index][0] + ($skillConfig['upgradeParams'][$index][0] * ($stage - 1) ) : '0';
                    $sum[ $map[$type ]] = add( $sum[ $map[$type] ] , $addNum );
                    $sumId[ $type ]     = add( $sumId[ $type ] ,$addNum);
                }
            }

        }

        return [ $sum , $sumId ];

    }

    public function getLvStage(int $lv,array $lvRange):int
    {
        foreach ($lvRange as $stage => $value) 
        {
            if($value[0] == $lv || $value[1] == $lv || $lv > $value[0] && $lv < $value[1] ) return $stage;
        }
    }

    public function getShowData(PlayerService $player,array $comrades):array
    {
        $list = [];
        $all  = ConfigComrade::getInstance()->getAll();
        foreach($all as $id => $config )
        {
            
            $detail = array_key_exists($id,$comrades) ? $comrades[$id] : ['state' => -1 ,'battle' => 0,'lv' => 0,'step' =>0 ] ;
            $detail['id'] = $id;
            $detail['target'] = [];
            if($detail['state'] != 1 && $config['quest_id'])
            {
                $taskConfig =  ConfigTask::getInstance()->getOne($config['quest_id']);

                list($num,$state) = TaskService::getInstance()->getTaskState($player,0,$taskConfig);

                $detail['target'] = [
                    'complete_type'   => $taskConfig['complete_type'],
                    'complete_params' => $taskConfig['complete_params'],
                    'val'             => $num,
                ];
            }

            $list[] = $detail;
        }

        return $list;
    }

    public function getRandReward(array $comradeIds,int $num ):array
    {
        list($map , $list) = ConfigComradeVisit::getInstance()->getRandVisit($comradeIds);
        $visit  = [];
        $reward = ['normal' => [],'like' => [] ];
        for ($i = 0; $i < $num; $i++) 
        { 
            $index   = randTable($map);
            $visit[] = $index;
            $detail  = $list[$index];
            if($detail['like_num'] > 0)
            {
                $reward['like'][]   = [  $detail['chara'] => $detail['like_num'] ];
            }else{
                $reward['normal'][] = $detail['normal_reward'];
            }
        }
        return [ $reward , $visit ];
    }

    public function getCountdown(PlayerService $playerSer):int
    {
        //无需恢复体力
        if( !$begin = $playerSer->getArg(Consts::COMRADE_ENERGY_TIME) ) return 0 ;
        
        $ttl = ConfigParam::getInstance()->getFmtParam('DESTINY_ENERGY_TIME');
        
        return $begin + $ttl - time();
    }

    public function getUnlockComrade(array $comrades):array
    {
        $list  = [];
        foreach ($comrades as $id => $detail) 
        {
            if(!$detail['state']) continue;
            $list[]  = $id;
        }
        return $list;
    }

    public function getLvStageByTalent(PlayerService $playerSer,int $talent)
    {
        $sum = 0;
        $comrades = $playerSer->getData('comrade');
        foreach ($comrades as $id => $detail) 
        {
            if($detail['state'] != 1) continue;
            
            $config = ConfigComrade::getInstance()->getOne($id);
            if($config['talent'] != $talent) continue;

            $lv = $this->getLvStage($detail['lv'],$config['talent_level_up']);
            if(!$lv) continue;
            
            $skillConfig  = ConfigSkill::getInstance()->getOne($talent);
            foreach ($skillConfig['type'] as $type)
            {
                $sum += $skillConfig['params'][0][0]  +  ( $skillConfig['upgradeParams'][0][0] * ( $lv - 1)) ;
            }
            
        }
        
        return $sum;
    }

    public function getComradeAttrAdd(&$attr,array $comrade,&$ratio):void
    {
        //未使用
        if(!$comrade) return ;
        list($detail,$_un) = $this->getComradeAttrSum($comrade);

        foreach ($detail as $attrName => $attrValue)
        {
            if(array_key_exists($attrName,$attr)){
                $attr[$attrName] = add($attr[$attrName],$attrValue);
            }else{
                $ratio[$attrName] = add($ratio[$attrName],$attrValue);
            }
        }
    }

    public function getLvStageByTalentCopy(PlayerService $playerSer,int $talent)
    {
        $sum = 0;
        $comrades = $playerSer->getData('comrade');
        foreach ($comrades as $id => $detail)
        {
            if($detail['state'] != 1) continue;
            $config = ConfigComrade::getInstance()->getOne($id);
            if($config['talent'] != $talent) continue;

            $skillConfig  = ConfigSkill::getInstance()->getOne($talent);
            foreach ($skillConfig['type'] as $type)
            {
                $sum += $skillConfig['params'][0][0]  +  ( $skillConfig['upgradeParams'][0][0] * ($this->getLvStage($detail['lv'],$config['talent_level_up']))) ;
            }
        }

        return $sum;
    }

}
