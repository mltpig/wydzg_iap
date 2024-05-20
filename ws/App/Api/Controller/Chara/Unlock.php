<?php
namespace App\Api\Controller\Chara;
use App\Api\Table\ConfigRoleChara;
use App\Api\Service\RoleService;
use App\Api\Controller\BaseController;

class Unlock extends BaseController
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

            $result = '无需再次解锁';
            if(!isset($chara[$type][$id]))
            {
                $cost   = $config['cost_id'];
                $hasNum = $this->player->getGoods($cost['gid']);
                $result = '数量不足';
                if($hasNum >= $cost['num'])
                {
                    $reward = [ [ 'type' => GOODS_TYPE_1,'gid' => $cost['gid'],'num' => -$cost['num'] ] ];
                    $this->player->goodsBridge($reward,'人物模型解锁',$hasNum );
                    
                    $this->player->setActivityChara(2,$config['id'],1,'set');

                    $this->player->setHead(3,0,$config['id'],'push');

                    $this->player->setData('user','chara',['type' => 1,'value' => strval($config['id']) ] );
                    // $this->player->setData('user','head', ['type' => 3,'value' => strval($config['id']) ] );


                    $result = [ 
                        'head' 	   => $this->player->getData('head'),
                        'chara'    => RoleService::getInstance()->getCharaFmt($this->player),
                        'user'     => $this->player->getUserInfo(),
                        'goods'    => $this->player->getGoodsInfo(),
                    ];
                }
            }
        }

        $this->sendMsg( $result);
    }

}