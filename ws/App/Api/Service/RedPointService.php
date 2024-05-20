<?php
namespace App\Api\Service;

use App\Api\Service\Module\SpiritService;
use App\Api\Service\Module\PetService;
use App\Api\Service\Module\TowerService;
use App\Api\Service\Module\DemonTrailService;
use App\Api\Service\Module\EquipmentService;
use App\Api\Service\Module\SecretTowerService;
use App\Api\Service\Module\MagicService;
use App\Api\Service\Module\FundService;
use App\Api\Service\Module\MonthlyCardService;
use App\Api\Service\Module\XianYuanService;
use App\Api\Service\Module\ShangGuService;
use App\Api\Service\ShopService;
use App\Api\Table\Activity\SignIn;
use App\Api\Utils\Keys;
use App\Api\Utils\Consts;
use EasySwoole\Redis\Redis;
use EasySwoole\Pool\Manager as PoolManager;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigTree;
use App\Api\Table\ConfigCloudStage;
use App\Api\Table\ConfigComradeChallenge;
use App\Api\Table\Activity\OptionalGiftbag;
use App\Api\Table\Activity\FirstRecharge;
use App\Api\Table\ConfigRoleChara;
use EasySwoole\Component\CoroutineSingleTon;
use EasySwoole\EasySwoole\ServerManager;

class RedPointService
{
    use CoroutineSingleTon;

    public function sendPoint(PlayerService $playerSer):void
    {
        $fd       = $playerSer->getData('fd');
        $data = [ 
            'code' => SUCCESS, 
            'method' => 'redPoint', 
            'data'  =>  [ 'redPoint' => $this->getRedPoints($playerSer) ]  
        ];
        ServerManager::getInstance()->getSwooleServer()->push($fd,json_encode($data,JSON_UNESCAPED_UNICODE|JSON_FORCE_OBJECT));
    }

    public function getRedPoints(PlayerService $playerSer):array
    {
        list($promotionReward,$promotionGoto) = RoleService::getInstance()->getPromotionRedPointInfo($playerSer);
        list($petActiveUpLv,$petAdFreeRefresh) = PetService::getInstance()->getPetRedPointInfo($playerSer);
        list($spirit,$spiritGroove,$spiritYoke,$spiritcj) = SpiritService::getInstance()->getSpiritRedPointInfo($playerSer);
        list($tacticalUnderstanding,$tacticalDeduce) = TacticalService::getInstance()->getRedPointInfo($playerSer);
        list($towerCombat,$towerPreinst)    = TowerService::getInstance()->getTowerRedPointInfo($playerSer);
        $demonTrail = DemonTrailService::getInstance()->getDemonTrailRedPointInfo($playerSer);
        list($equipmentUp,$equipmentHm)     = EquipmentService::getInstance()->getEquipmentRedPointInfo($playerSer);
        list($secretTowerFloor,$secretTowerAchievement)     = SecretTowerService::getInstance()->getSecretTowerRedPointInfo($playerSer);
        list($magicDraw,$magicUp,$magicStage,$magicIh)      = MagicService::getInstance()->getMagicRedPointInfo($playerSer);
        list($fundLv,$fundgk,$fundJq,$fundDq)               = FundService::getInstance()->getFundRedPointInfo($playerSer);
        list($xianyuanTask,$xianyuanFund,$xianyuanSign,$xianyuanGiftFree,$xianyuanGiftTask) = XianYuanService::getInstance()->getXianYuanRedPointInfo($playerSer);

        return [
            'activityFirstRecharge'         => $this->getFirstRecharge($playerSer),
            'activityOptionalFreeGiftbag'   => $this->getOptionalFreeGiftbag($playerSer),
            'activitySignIn'                => $this->getSignIn($playerSer),
            'activityZjJump'                => $playerSer->getArg(Consts::ACTIVITY_CHANNEL_TASK_4) ? false : true,
            'activityLogin'                 => $this->getLoginReward($playerSer),
            'activityNewYear'               => $this->getNewYearReward($playerSer),
            'activityCircleOfFriends'       => ActivityService::getInstance()->getCircleOfFriendsRedPoint($playerSer),
            'email'                         => $this->getEmail($playerSer),
            'campComradeBattle'             => $this->getComradeBattle($playerSer),
            'campComradeVisit'              => $this->getComradeVisit($playerSer),
            'campWorkerFree'                => $this->getWorkerFree($playerSer),
            'campWorkerHire'                => $this->getWorkerHire($playerSer),
            'challengeArenaTicket'          => $this->getArenaTicket($playerSer),
            'challengeBoss'                 => $this->getGeneralTicket($playerSer),
            'challengeInvade'               => $this->getInvadeCount($playerSer),
            'chapter'                       => $this->getChapter($playerSer),
            'cloud'                         => $this->getCloud($playerSer),
            'armyFlag'                      => $this->getArmyFlag($playerSer),
            'chara'                         => $this->getChara($playerSer),
            'promotionReward'               => $promotionReward,
            'promotionGoto'                 => $promotionGoto,
            'petActiveUpLv'                 => $petActiveUpLv,
            'petAdFreeRefresh'              => $petAdFreeRefresh,
            'spirit'                        => $spirit,
            'spiritGroove'                  => $spiritGroove,
            'spiritYoke'                    => $spiritYoke,
            'spiritcj'                      => $spiritcj,
            'tacticalUnderstanding'         => $tacticalUnderstanding,//代表阵法可以参悟
            'tacticalDeduce'                => $tacticalDeduce,//代表阵法可以推演
            'towerCombat'                   => $towerCombat,
            'towerPreinst'                  => $towerPreinst,
            'demonTrail'                    => $demonTrail,
            'equipmentUp'                   => $equipmentUp,
            'equipmentHm'                   => $equipmentHm,
            'secretTowerFloor'              => $secretTowerFloor,
            'secretTowerAchievement'        => $secretTowerAchievement,
            'magicDraw'                     => $magicDraw,
            'magicUp'                       => $magicUp,
            'magicStage'                    => $magicStage,
            'magicIh'                       => $magicIh,
            'fundLv'                        => $fundLv,
            'fundgk'                        => $fundgk,
            'fundJq'                        => $fundJq,
            'fundDq'                        => $fundDq,
            'xianyuanTask'                  => $xianyuanTask,
            'xianyuanFund'                  => $xianyuanFund,
            'xianyuanSign'                  => $xianyuanSign,
            'xianyuanGiftFree'              => $xianyuanGiftFree,
            'xianyuanGiftTask'              => $xianyuanGiftTask,
            'shangguSign'                   => ShangGuService::getInstance()->getShangGuRedPointInfo($playerSer),
            'shopOverFlow'                  => ShopService::getInstance()->getShopRedPointInfo($playerSer),
        ];
    }

    public function getFirstRecharge(PlayerService $playerSer):array
    {
        $config = FirstRecharge::getInstance()->getAll();
        
        $list = [];

        foreach ($config as $detail) 
        {
            //充值标志
            $state = $playerSer->getArg(  Consts::ACTIVITY_FIRST_RECHARGE_TAG + $detail['id']);
            $list[ $detail['id'] ] = $state ? false : true;
        }

        return  $list;
    }

    public function getOptionalFreeGiftbag(PlayerService $playerSer):bool
    {
        $isRed = false;
        $list  = OptionalGiftbag::getInstance()->getAll();
        foreach ($list as $groupid => $value) 
        {
            if($value['giftbag_type'] != 1) continue;
            if(!$playerSer->getArg($groupid)) return true;
        }

        return  $isRed;
    }

    public function getLoginReward(PlayerService $playerSer):bool
    {
        $config  = ActivityService::getInstance()->getLoginRewardConfig();

        $hour  = date('H');
        $isRed = false;
        foreach ($config as $actid => $value)
        {
            if($playerSer->getArg($actid)) continue;
            if($hour > $value['begin'] && $hour < $value['end'] || $hour == $value['begin']) return true;
        }

        return  $isRed;
    }

    public function getNewYearReward(PlayerService $playerSer):bool
    {
        $time   = time();
        $begin  = strtotime(Consts::ACTIVITY_NEW_YEAR_BEGIN);
        $end    = strtotime(Consts::ACTIVITY_NEW_YEAR_END);

        if($time > $begin && $time < $end || $time == $begin)
        {
            $task = $playerSer->getData('task');
    
            $bosx = ActivityService::getInstance()->getNewYearBoxs($playerSer,$task);
            $states = array_column($bosx,'state','id');
    
            return  in_array(1,$states);
        }

        return false;
    }

    public function getSignIn(PlayerService $playerSer):bool
    {
        $isRed = false;

        list($day,$idState)  = $playerSer->getTmp('sign_in');
        $config = SignIn::getInstance()->getAll();
        foreach ($config as $value) 
        {
            // 0未达成  1 已达成 2已领取
            $state = $day >= $value['day_num'] ? 1 : 0;
            if($idState >= $value['id']) $state = 2;
            if($state == 1) return true;
        }
        
        return  $isRed;
    }

    public function getEmail(PlayerService $playerSer):bool
    {
        $isRed = false;
        $emailKey  = Keys::getInstance()->getEmailKey($playerSer->getData('openid'),$playerSer->getData('site'),1);
        $emailList = PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($emailKey) {
            return $redis->hGetAll($emailKey);
        });

        $now  = time();
        foreach ($emailList as $value) 
        {
            $detail = json_decode($value,true);
            if($detail['end_time'] < $now) continue;
            if(!$detail['state']) return true;
            if($detail['state'] != 2 && $detail['reward'] ) return 1;
        }
        
        return  $isRed;
    }

    public function getComradeVisit(PlayerService $playerSer):int
    {
        return $playerSer->getArg(Consts::COMRADE_ENERGY);
    }

    public function getComradeBattle(PlayerService $playerSer):array
    {
        $list     = [];
        $roleLv   = $playerSer->getData('role','lv');
        $comrades = $playerSer->getData('comrade');

        foreach ($comrades as $cid => $detail) 
        {
            $config = ConfigComradeChallenge::getInstance()->getBattleId($cid,$detail['battle'] + 1);

            if($detail['state'] && $roleLv >= $config['unlock_level'] && $detail['lv'] >= $config['unlock_like']) $list[] = $cid;
        }

        return $list;
    }

    public function getWorkerFree(PlayerService $playerSer):int
    {
        $num   = 0;
        $workers = $playerSer->getData('paradise','worker')['list'];
        foreach ($workers as $cid => $worker) 
        {
            if($worker) continue;
            $num++;
        }
        return $num;
    }

    public function getWorkerHire(PlayerService $playerSer):bool
    {
        $reds  = false;
        $count = count($playerSer->getData('paradise','worker')['list']);
        $costs = ConfigParam::getInstance()->getFmtParam('HOMELAND_WORKER_COST');
        
        if(count($costs) + 1 <=  $count ) return 0;
        
        $cost  = $costs[ $count - 1];

        if($playerSer->getGoods($cost['gid']) >= $cost['num'] ) return 1;

        return $reds;
    }

    public function getArenaTicket(PlayerService $playerSer):int
    {
        $costGoods = ConfigParam::getInstance()->getFmtParam('PVP_CHALLENGE_COST');
        return $playerSer->getGoods($costGoods['gid']);
    }

    public function getInvadeCount(PlayerService $playerSer):int
    {
        $config = ConfigParam::getInstance()->getFmtParam('INVADE_CHALLENGE_TIME');
        return array_sum($config) - $playerSer->getArg(INVADE);
    }

    public function getGeneralTicket(PlayerService $playerSer):int
    {
        $limit = ConfigParam::getInstance()->getFmtParam('WILDBOSS_REPEAT_LIMIT');
        if(MonthlyCardService::getInstance()->getMonthlyCardExpire($playerSer)) $limit += 2;//月卡上限+2

        return $limit - $playerSer->getArg(CHALLENGE);
    }

    public function getChapter(PlayerService $playerSer):bool
    {
        $task = $playerSer->getData('task');
        $id   = TaskService::getInstance()->getAdminTask( $task,2 );
        
        return $task[$id][1] == 1;
    }

    public function getCloud(PlayerService $playerSer):bool
    {
        $cloud  = $playerSer->getData('cloud');
        $config = ConfigCloudStage::getInstance()->getOne($cloud['stage'],$cloud['lv']);

        if(!$config) return false;
        
        $cost = $config['advance_cost'] ? $config['advance_cost'] : ['gid' => $config['cost']['gid'] ,'num' => 1 ]; 

        return $playerSer->getGoods($cost['gid']) >= $cost['num'];
    }

    public function getArmyFlag(PlayerService $playerSer):bool
    {
        $lv        = $playerSer->getData('tree','lv');
        $state     = $playerSer->getData('tree','state');
        $maxLevel  = ConfigTree::getInstance()->getMaxLevel();

        if($lv >= $maxLevel) return false;
        if($state)
        {
            //军旗升级 有
            $cost   = ConfigParam::getInstance()->getFmtParam('DREAM_UPGRADE_SPEEDUP_ITEM_COST');
            $hasNum = $playerSer->getGoods($cost['gid']);  
            return $hasNum > 0 ;
        }else{
            $cost = ConfigTree::getInstance()->getOne($lv)['cost'];
            return  $playerSer->getGoods($cost['gid']) >= $cost['num'];
        }
    }

    public function getChara(PlayerService $playerSer):array
    {
        $charaConfig = ConfigRoleChara::getInstance()->getActivityAll();

        $list = [];
        $chara = $playerSer->getData('chara');
        foreach ($charaConfig as $id => $config) 
        {
            $cost   = $config['cost_id'];
            $hasNum = $playerSer->getGoods($cost['gid']);
            //升级
            $costNum = isset($chara[$config['get_type']][$id]) ? $cost['step'] : $cost['num'];
            if($costNum > $hasNum) continue;

            $list[] = intval($id);
        }

        return $list;
    }



}
