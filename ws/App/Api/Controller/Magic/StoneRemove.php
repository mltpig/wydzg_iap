<?php
namespace App\Api\Controller\Magic;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigMagic;
use App\Api\Table\ConfigStone;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\MagicService;
use App\Api\Service\TaskService;

class StoneRemove extends BaseController
{

    public function index()
    {
        $param  = $this->param;
        $bag    = $this->player->getData('magic','bag');

        $result = '未衍化';
        if(array_key_exists($param['id'],$bag))
        {
            $old       = $bag[$param['id']];
            $stone_id  = $old['stone'][$param['index']];
            $old['stone'][$param['index']]  = 0;

            $this->player->setMagic('bag',$param['id'],$old,'multiSet');

            $this->player->goodsBridge([[ 'type' => GOODS_TYPE_23, 'gid' => $stone_id, 'num' => 1 ]],'下阵刻印',$stone_id);

            $result = [
                'magic' => MagicService::getInstance()->getMagicFmtData($this->player),
                'goods' => $this->player->getGoodsInfo(),
            ];
        }
        
        $this->sendMsg( $result );
    }

}