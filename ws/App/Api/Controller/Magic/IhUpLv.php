<?php
namespace App\Api\Controller\Magic;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigMagic;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\MagicService;
use App\Api\Service\TaskService;

class IhUpLv extends BaseController
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
            $result = '未激活';
            if($old > 0)
            {
                $config = ConfigParam::getInstance()->getFmtParam("MAGIC_COMBINE_LEVEL");
                $result = '已达到顶级';
                if($old < count($config))
                {
                    $state  = MagicService::getInstance()->getUpCombine($param['id'],$magic['combine'],$magic['bag']);
                    $result = '升级条件尚未达成';
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
        }
        $this->sendMsg( $result );
    }

}