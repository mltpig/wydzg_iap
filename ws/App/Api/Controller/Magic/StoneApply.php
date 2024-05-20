<?php
namespace App\Api\Controller\Magic;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigMagic;
use App\Api\Table\ConfigStone;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\MagicService;
use App\Api\Service\TaskService;

class StoneApply extends BaseController
{

    public function index()
    {
        $param  = $this->param;
        $bag    = $this->player->getData('magic','bag');

        $result = '未衍化';
        if(array_key_exists($param['id'],$bag))
        {
            $config         = ConfigMagic::getInstance()->getOne($param['id']);
            $config_stone   = ConfigStone::getInstance()->getOne($param['stone_id']);

            $stone_unlock   = explode("|",$config['stone_unlock']);
            $stone_type     = explode("|",$config['stone_type']);
            $result = '装备条件不满足';
            if($bag[$param['id']]['lv'] >= $stone_unlock[$param['index'] - 1] && in_array($config_stone['type'],$stone_type))
            {
                $old       = $bag[$param['id']];
                $old['stone'][$param['index']] = $param['stone_id'];

                $this->player->setMagic('bag',$param['id'],$old,'multiSet');

                $this->player->goodsBridge([[ 'type' => GOODS_TYPE_23, 'gid' => $param['stone_id'], 'num' => -1 ]],'上阵刻印',$param['stone_id']);

                $result = [
                    'magic' => MagicService::getInstance()->getMagicFmtData($this->player),
                    'goods' => $this->player->getGoodsInfo(),
                ];
            }
        }
        
        $this->sendMsg( $result );
    }

}