<?php
namespace App\Api\Controller\Chara;
use App\Api\Table\ConfigRoleChara;
use App\Api\Service\RoleService;
use App\Api\Controller\BaseController;

class Upgrade extends BaseController
{

    public function index()
    {
        $id   = $this->param['id'];
        $type = $this->param['type'];

        $config = ConfigRoleChara::getInstance()->getActivityOne($id);
        $result = '该模型暂未解锁';
        if($config && $config['get_type'] == $type)
        {
            $chara = $this->player->getData('chara');

            $result = '未解锁';
            if(isset($chara[$type][$id]))
            {
                $cost   = $config['cost_id'];
                $hasNum = $this->player->getGoods($cost['gid']);
                $result = '数量不足';
                if($hasNum >= $cost['step'])
                {
                    $reward = [ [ 'type' => GOODS_TYPE_1,'gid' => $cost['gid'],'num' => -$cost['num'] ] ];
                    $this->player->goodsBridge($reward,'人物模型升级',$hasNum );

                    $this->player->setActivityChara(2,$config['id'],$cost['step'],'add');
                    $result = [ 
                        'chara'    => RoleService::getInstance()->getCharaFmt($this->player),
                        'goods'    => $this->player->getGoodsInfo(),
                    ];
                }
            }
        }

        $this->sendMsg( $result);
    }

}