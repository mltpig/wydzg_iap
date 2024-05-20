<?php
namespace App\Api\Controller\Cloud;
use App\Api\Table\ConfigCloud;
use App\Api\Controller\BaseController;

//升级
class Unlock extends BaseController
{

    public function index()
    {
        $cloudid  = $this->param['id'];

        $cloud  = $this->player->getData('cloud');
        
        $result = '已解锁';
        if(!in_array($cloudid,$cloud['list']) )
        {
            $config = ConfigCloud::getInstance()->getOne($cloudid);

            $result = '无效的ID';
            if($config)
            {
                $cost = $config['cost'];

                $hasNum = $this->player->getGoods($cost['gid']);
                $result = '数量不足';
                if( $hasNum >= $cost['num'])
                {

                    $cost['num'] = -$cost['num'];
                    $this->player->goodsBridge([ $cost ],'附魂解锁',$cloudid);

                    $this->player->setCloud('list',$cloudid,'push');

                    $result = [ 
                        'cloud'  => $this->player->getData('cloud'),
                        'remain' => $this->player->getGoods($cost['gid']),
                    ];
                }
    
            }
        }


        $this->sendMsg( $result );
    }

}