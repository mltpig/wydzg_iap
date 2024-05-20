<?php
namespace App\Api\Controller\Spirit;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigSpirits;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\SpiritService;
use App\Api\Service\TaskService;

class FetterUpLv extends BaseController
{

    public function index()
    {
        $param = $this->param;
        $id    = $param['id'];
        $bag     = $this->player->getData('spirit','bag');
        $yoke    = $this->player->getData('spirit','yoke');

        $result = '未解锁';
        if(array_key_exists($id,$yoke))
        {
            $result = '已达到顶级';
            $old    = $yoke[$id];
            if($old < 22)  //数值表写死Lv22
            {
                $result = '升级条件尚未达成';
                $state  = SpiritService::getInstance()->getUpFetter($id,$yoke,$bag);
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