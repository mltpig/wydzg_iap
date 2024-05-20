<?php
namespace App\Api\Controller\Dongfu\Comrade;
use App\Api\Table\ConfigComradeChallenge;
use App\Api\Controller\BaseController;
use App\Api\Service\BattleService;
use App\Api\Service\MosterBattleService;
//妖王战斗
class Battle  extends BaseController
{

    public function index()
    {
        $id      = $this->param['id'];
        $comrades = $this->player->getData('comrade');
        
        $result = '贤士未激活';
        if(array_key_exists($id,$comrades) )
        {
            $result = '贤士未解锁';
            $detail = $comrades[$id];
            if($detail['state'])
            {
                $nextBattle = $detail['battle'] + 1;
    
                $config = ConfigComradeChallenge::getInstance()->getBattleId($id,$nextBattle);
                $result = '已达到顶级';
                if($config)
                {
                    $roleLv = $this->player->getData('role','lv');
        
                    $result = '未解锁';
                    if($detail['state'] && $roleLv >= $config['unlock_level'] && $detail['lv'] >= $config['unlock_like'])
                    {
                        $reward = [];
                        $selfData     = BattleService::getInstance()->getBattleInitData($this->player);
                        $selfShowData = BattleService::getInstance()->getBattleShowData($this->player);
                        list($isWin,$battleLog,$selfHp,$enemyHp,$enemyAdd) = MosterBattleService::getInstance()->battle(
                            $selfData,$config['monster_id'],$config['monster_level'],$selfShowData);
        
                        if($isWin)
                        {
                            $reward = json_decode( $config['rewards'] , true );
                            $this->player->setComrade($id,'battle',1,'add');
                            $this->player->goodsBridge($reward,'贤士切磋胜利',$id.'|'.$nextBattle);
                        }

                        $comrades = $this->player->getData('comrade',$id);

                        $result = [
                            'now'          => [ 'battle' => $comrades['battle'] ],
                            'battleDetail' => [
                                'isWin'  => intval($isWin),
                                'log'    => $battleLog,
                                'reward' => $reward,
                                'self'   => ['hp' => $selfHp ,'add' => $selfShowData,'chara' => $this->player->getData('user','chara') ],//附魂 模型
                                'enemy'  => ['hp' => $enemyHp,'mosterid' => $config['monster_id'] ,
                                    'add' => $enemyAdd,
                                    "mosterLv" => $config['monster_level']],
                            ]
                        ];
                    }
                }
            }
        }
        
        $this->sendMsg( $result );
    }

}