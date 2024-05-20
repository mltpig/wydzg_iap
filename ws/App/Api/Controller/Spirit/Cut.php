<?php
namespace App\Api\Controller\Spirit;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigSpirits;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\SpiritService;
use App\Api\Service\TaskService;

class Cut extends BaseController
{

    public function index()
    {
        $param = $this->param;
        $index = $param['squad'];

        $squad  = $this->player->getData('spirit','squad');

        $result = '无效的阵容';
        if(array_key_exists($index,$squad))
        {
            $this->player->setSpirit('active', 0, $index, 'set');

            $result = [ 
                'spirit' => SpiritService::getInstance()->getSpiritFmtData( $this->player, $this->player->getArg( Consts::SPIRIT_AD_TAG )),
            ];
        }
        $this->sendMsg( $result );
    }

}