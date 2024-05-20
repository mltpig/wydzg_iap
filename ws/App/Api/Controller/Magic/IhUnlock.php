<?php
namespace App\Api\Controller\Magic;
use App\Api\Table\ConfigParam;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\MagicService;
use App\Api\Service\TaskService;

class IhUnlock extends BaseController
{

    public function index()
    {
        $param   = $this->param;
        $magic   = $this->player->getData('magic');
        $combine = MagicService::getInstance()->getCombine();

        $result = '未解锁';
        if(array_key_exists($param['id'],$combine))
        {
            $old    = $magic['combine'][$param['id']];
            $result = '以激活';
            if($old == 0)
            {
                $state  = MagicService::getInstance()->getStateCombine($param['id'],$magic['bag']);
                $result = '激活条件尚未达成';
                if($state)
                {
                    $old++;
                    $this->player->setMagic('combine',$param['id'],$old,'multiSet');
                    $result = [
                        'magic' => MagicService::getInstance()->getMagicFmtData($this->player),
                    ];
                }
            }
        }
        $this->sendMsg( $result );
    }

}