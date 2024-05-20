<?php
namespace App\Api\Controller\Chapter;
use App\Api\Table\ConfigChapter;
use App\Api\Service\TaskService;
use App\Api\Service\EquipService;
use App\Api\Service\BattleService;
use App\Api\Service\MosterBattleService;
use App\Api\Controller\BaseController;

//抽卡
class Battle  extends BaseController
{

    public function index()
    {
        $chapter = $this->player->getData('chapter');

        $config = ConfigChapter::getInstance()->getOne($chapter);
        $result = '还未叛乱，无需平叛';
        if($config)
        {
            $next = ConfigChapter::getInstance()->getOne($chapter+1);
            $result = '海内太平，已无叛乱';
            if($next)
            {
                $reward = [];

                $selfData = BattleService::getInstance()->getBattleInitData($this->player);
                $selfShowData = BattleService::getInstance()->getBattleShowData($this->player);

                list($isWin,$battleLog,$selfHp,$enemyHp,$enemyAdd) = MosterBattleService::getInstance()->battle(
                    $selfData,$config['moster_list'],$config['moster_level_list'],$selfShowData);
                
                if($isWin)
                {
                    $reward = $config['rewards'];
                    $this->player->passChapter();
                    $this->player->goodsBridge($config['rewards'],'关卡挑战胜利',$chapter);
                    $chapter = $this->player->getData('chapter');
                    TaskService::getInstance()->setVal($this->player,3,$chapter-1,'set');
                }

                //通过比记录小一级
                TaskService::getInstance()->setVal($this->player,51,1,'add');
    
                $result = [
                    'now'   => [
                        'chapterid'  => $chapter,
                    ],
                    'battleDetail' => [
                        'isWin'  => intval($isWin),
                        'log'    => $battleLog,
                        'reward' => $reward,
                        'self'   => ['hp' => $selfHp ,'add' => $selfShowData,'chara' => $this->player->getData('user','chara') ],//附魂 模型
                        'enemy'  => ['hp' => $enemyHp,'mosterid' => $config['moster_list'] ,
                            'add' => $enemyAdd,
                            "mosterLv" => $config['moster_level_list']],
                    ]
                ];
            }
        }
        $this->sendMsg( $result );
    }

}