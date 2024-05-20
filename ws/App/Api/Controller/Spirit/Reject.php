<?php
namespace App\Api\Controller\Spirit;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigSpirits;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\SpiritService;
use App\Api\Service\TaskService;

class Reject extends BaseController
{

    public function index()
    {
        $param = $this->param;
        $index = $param['squad'];
        $id    = $param['id'];

        $bag    = $this->player->getData('spirit','bag');
        $squad  = $this->player->getData('spirit','squad');

        $where = SpiritService::getInstance()->getStateDiff([$id],$bag);

        $result = '未解锁';
        if(empty($where))
        {
            $result = '无效的阵容';
            if(array_key_exists($index,$squad))
            {

                $elementsNotInArray = array_diff([$id], $squad[$index]);
                $result = '不在阵容内';
                if(empty($elementsNotInArray)){

                    $old = $squad[$index];
                    $old = array_diff($old, [$id]);
                    $old = array_values($old);
                    
                    $this->player->setSpirit('squad',$index,$old,'multiSet');

                    $result = [ 
                        'spirit' => SpiritService::getInstance()->getSpiritFmtData( $this->player, $this->player->getArg( Consts::SPIRIT_AD_TAG )),
                    ];
                }
            }
        }
        $this->sendMsg( $result );
    }

}