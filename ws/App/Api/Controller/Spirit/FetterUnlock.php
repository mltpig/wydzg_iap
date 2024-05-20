<?php
namespace App\Api\Controller\Spirit;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigSpirits;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\SpiritService;
use App\Api\Service\TaskService;

class FetterUnlock extends BaseController
{

    public function index()
    {
        $param = $this->param;
        $id      = $param['id'];
        $bag     = $this->player->getData('spirit','bag');
        $yoke    = $this->player->getData('spirit','yoke');

        $result = '未解锁';
        if(array_key_exists($id,$yoke))
        {
            $result = '已激活';
            $old    = $yoke[$id];
            if($yoke[$id] == 0)
            {
                $result = '激活条件尚未达成';
                $state  = SpiritService::getInstance()->getStateFetter($id,$bag);
                if(empty($state))
                {
                    $old++;
                    $this->player->setSpirit('yoke',$id,$old,'multiSet');
                    
                    $result = [ 
                        'spirit' => SpiritService::getInstance()->getSpiritFmtData( $this->player, $this->player->getArg( Consts::SPIRIT_AD_TAG )),
                    ];
                }
            }
        }

        $this->sendMsg( $result );
    }

}