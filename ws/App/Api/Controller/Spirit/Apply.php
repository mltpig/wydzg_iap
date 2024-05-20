<?php
namespace App\Api\Controller\Spirit;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigSpirits;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\SpiritService;
use App\Api\Service\TaskService;

class Apply extends BaseController
{

    public function index()
    {
        $param = $this->param;
        $index = $param['squad'];

        $bag    = $this->player->getData('spirit','bag');
        $groove = $this->player->getData('spirit','groove');
        $squad  = $this->player->getData('spirit','squad');

        $where = SpiritService::getInstance()->getStateDiff([$param['id']],$bag);

        $result = '未解锁';
        if(empty($where))
        {
            $result = 'squad 无效的阵容';
            if(array_key_exists($index,$squad))
            {

                $result = '已在阵容中';
                if(!in_array($param['id'], $squad[$index]))
                {
                    
                    $result = '已达阵容上限';
                    if(count($squad[$index]) + 1 <= $groove)
                    {
                        $old    = $squad[$index];
                        $old[]  = $param['id'];
    
                        $this->player->setSpirit('squad',$index,$old,'multiSet');
    
                        $result = [ 
                            'spirit' => SpiritService::getInstance()->getSpiritFmtData( $this->player, $this->player->getArg( Consts::SPIRIT_AD_TAG )),
                        ];
                    }
                }
            }
        }
        $this->sendMsg( $result );
    }

}