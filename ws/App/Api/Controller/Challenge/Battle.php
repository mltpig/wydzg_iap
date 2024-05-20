<?php
namespace App\Api\Controller\Challenge;
use App\Api\Table\ConfigChallengeBoss;
use App\Api\Table\ConfigRole;
use App\Api\Table\ConfigParam;
use App\Api\Service\TaskService;
use App\Api\Service\BattleService;
use App\Api\Service\MosterBattleService;
use App\Api\Controller\BaseController;

//妖王战斗
class Battle  extends BaseController
{

    public function index()
    {

        //挑战的永远是下一级
        $nextid = $this->player->getData('challenge') + 1;

        $result = '海内太平，天下无妖';
        $next = ConfigChallengeBoss::getInstance()->getOne( $nextid );
        if($next)
        {
            $roleConfig = ConfigRole::getInstance()->getOne( $this->player->getData('role','lv') );
            $result = '暂未解锁';
            if($roleConfig['type'] >= $next['unlock_level'] )
            {

                $selfData     = BattleService::getInstance()->getBattleInitData($this->player);
                $selfShowData = BattleService::getInstance()->getBattleShowData($this->player);
                list($isWin,$battleLog,$selfHp,$enemyHp,$enemyAdd) = MosterBattleService::getInstance()->battle(
                    $selfData,$next['moster_list'],$next['moster_level_list'],$selfShowData);

                $reward = [];
                if($isWin)
                {
                    $reward = $next['rewards'];
                    $this->player->goodsBridge($reward,'妖王挑战胜利',$nextid);
                    $this->player->setData('challenge',null,$nextid);
                }
                
                $count  = $this->player->getArg(CHALLENGE);
                $config = ConfigChallengeBoss::getInstance()->getOne( $nextid );
                $cost   = $config['repeat_cost'];
                //首次消耗免费
                $config = ConfigParam::getInstance()->getFmtParam('WILDBOSS_REPEAT_COST_PARAM');
                $number = $cost['num'] * ($config[$count]/1000);
                TaskService::getInstance()->setVal($this->player,27,1,'add');

                $result = [
                    'now' => [
                        'lv'        => intval($this->player->getData('challenge')),
                        'cost_num'  => $number,
                    ],
                    'battleDetail' => [
                        'isWin'  => intval($isWin),
                        'log'    => $battleLog,
                        'reward' => $reward,
                        'self'   => ['hp' => $selfHp ,'add' => $selfShowData,'chara' => $this->player->getData('user','chara') ],//附魂 模型
                        'enemy'  => ['hp' => $enemyHp,'mosterid' => $next['moster_list'],
                            'add' => $enemyAdd,
                            "mosterLv" => $next['moster_level_list']],
                    ]
                ];
            }   
        }
        
        
        $this->sendMsg( $result );
    }

}