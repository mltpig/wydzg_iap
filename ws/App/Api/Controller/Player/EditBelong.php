<?php
namespace App\Api\Controller\Player;
use App\Api\Controller\BaseController;
use App\Api\Service\RoleService;
use App\Api\Table\ConfigParam;
use App\Api\Utils\Consts;

class EditBelong extends BaseController
{

    public function index()
    {
        $belong = $this->param['belong'];

        $result = '物品数量不足';

        //获取需要的消耗物品的配置 物品id；数量
        $costConfig = ConfigParam::getInstance()->getFmtParam('BODYCHANGE_ITEM_ID');
        //获取物品数量
        $hasNum = $this->player->getGoods($costConfig['gid']);
        $number =$costConfig['num'];//使用阵法晶魄数量


        if( $number <= $hasNum )
        {
            //设置模型属性
            $this->player->setArg(Consts::CHARA_BELONG,$belong,'reset');

            //扣除物品
            $this->player->goodsBridge([[ 'type' => GOODS_TYPE_1,'gid' => $costConfig['gid'],'num' => -$number ]],'阵法',$hasNum );
            $remain = $hasNum - $number;
            //返回结果
            $result = [
                'chara' 	=> RoleService::getInstance()->getCharaFmt($this->player),
                'user'		=> $this->player->getUserInfo(),
                'head' 		=> $this->player->getData('head'),
                'remain'    => $remain,//剩余物品数量
            ];
        }

        $this->sendMsg( $result);
    }

}