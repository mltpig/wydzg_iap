<?php
namespace App\Api\Controller\Tower;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigTower;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\TowerService;
use App\Api\Service\TaskService;

class Moppingup extends BaseController
{

    public function index()
    {
        $tower  = $this->player->getData('tower');
        $high   = $this->player->getArg( Consts::TOWER_HIGH_RECORD );
        $config = ConfigTower::getInstance()->getOne($high);

        $result = '会当凌绝顶，一览众山小';
        if($config)
        {
            $result = '通关第3层';
            if($config['floor'] >= 4)
            {
                $reward         = [];
                $reset          = ConfigParam::getInstance()->getFmtParam('TOWER_RESET_PARAM') + 0;
                $tiers          = ConfigTower::getInstance()->getFloor($config['floor'] - $reset);
                sort($tiers);
                $resetid = $tiers[0]; // 重置到第几层的ID

                $pass_floor     = $config['floor'] - 1; // 实际通关层
                $award_floor    = 1; // 默认通关层
                $param = ConfigParam::getInstance()->getFmtParam('TOWER_MOPPINGUP_LIMIT');
                foreach($param as $k => $v)
                {
                    if($pass_floor >= $k)
                    {
                        $award_floor = $v;
                    }
                }

                $awards = ConfigTower::getInstance()->getFloorAll($award_floor);
                $reward = TowerService::getInstance()->aggregateAwards($awards);

                foreach($awards as $moppingupAward)
                {
                    $this->player->goodsBridge($moppingupAward['repeat_rewards'],'千里走单骑扫荡奖励',$high);
                }

                $num = $config['floor'] - $reset;
                $this->player->setTower('buffnum',0,$num - 1,'set');
                $this->player->setTower('towerid',0,$resetid,'set');

                $result = [
                    'tower'     => TowerService::getInstance()->getTowerFmtData($this->player),
                    'reward'    => $reward,
                ];
            }
        }

        $this->sendMsg( $result );
    }

}