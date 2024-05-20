<?php
namespace App\Api\Controller\Spirit;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigSpirits;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\SpiritService;
use App\Api\Service\TaskService;

class ApplySquad extends BaseController
{

    public function index()
    {
        $param = $this->param;

        $result = 'ids 格式错误';
        if(is_array($param['ids']) || empty($param['ids']))
        {
            $bag    = $this->player->getData('spirit','bag');
            $groove = $this->player->getData('spirit','groove');

            $where = SpiritService::getInstance()->getStateDiff($param['ids'],$bag);

            $result = '未解锁';
            if(empty($where))
            {
                $result = '以达阵容上限';
                if(count($param['ids']) <= $groove){

                    $this->player->setSpirit('squad',$param['squad'],$param['ids'],'multiSet');
                }

                $result = [ 
                    'spirit' => SpiritService::getInstance()->getSpiritFmtData( $this->player, $this->player->getArg( Consts::SPIRIT_AD_TAG )),
                ];
            }
        }
        $this->sendMsg( $result );
    }

}