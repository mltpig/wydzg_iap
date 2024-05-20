<?php
namespace App\Api\Controller\Spirit;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigSpirits;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\SpiritService;
use App\Api\Service\TaskService;

class UnlockBox extends BaseController
{

    public function index()
    {
        $groove = $this->player->getData('spirit','groove');
        $roleLv = $this->player->getData('role','lv');

        $groove++;
        $index = $groove - 1;

        $where = ConfigParam::getInstance()->getFmtParam('SPIRIT_BOX_UNLOCK');

        $result = '已达到上限';
        if(isset($where[$index])){

            $result = '等级不足';
            if( $roleLv >= $where[$index])
            {
                
                $result = '已达到顶级';
                if($groove <= 3)
                {
                    $this->player->setSpirit('groove',0,$groove,'set');
    
                    $result = [ 
                        'spirit' => SpiritService::getInstance()->getSpiritFmtData( $this->player, $this->player->getArg( Consts::SPIRIT_AD_TAG )),
                    ];
                }
            }
        }
        $this->sendMsg( $result );
    }

}