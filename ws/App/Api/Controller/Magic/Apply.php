<?php
namespace App\Api\Controller\Magic;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigMagic;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\MagicService;
use App\Api\Service\TaskService;

class Apply extends BaseController
{

    public function index()
    {
        $param  = $this->param;
        $bag    = $this->player->getData('magic','bag');

        $result = '未衍化';
        if(array_key_exists($param['id'],$bag))
        {
            $config = ConfigMagic::getInstance()->getOne($param['id']);
            $this->player->setMagic('active',$config['type'],$param['id'],'multiSet');

            $result = [
                'magic' => MagicService::getInstance()->getMagicFmtData($this->player),
            ];
        }
        
        $this->sendMsg( $result );
    }

}