<?php
namespace App\Api\Controller\Tree;
use App\Api\Table\ConfigTree;
use App\Api\Service\TreeService;
use App\Api\Controller\BaseController;

//仙树升级
class Upgrade extends BaseController
{

    public function index()
    {

        $lv        = $this->player->getData('tree','lv');
        $state     = $this->player->getData('tree','state');
        $maxLevel  = ConfigTree::getInstance()->getMaxLevel();

        $result = '仙树已达到最高等级';
        if($maxLevel > $lv )
        {
            $config = ConfigTree::getInstance()->getOne($lv);
            
            $cost   = $config['cost'];
            $hasNum = $this->player->getGoods($cost['gid']);   

            $result = '灵石不足';
            if($hasNum >= $cost['num'])
            {
                $result = '仙树正在升级中';
                if(!$state)
                {
                    $cost['num'] = 0 - $cost['num'];
                    $this->player->goodsBridge([ $cost ],'军旗升级',$lv);

                    $this->player->setData('tree','state',1);
                    $this->player->setData('tree','timestamp', time() + $config['time']);

                    $result = [
                        'goods'     => $this->player->getGoodsInfo(),
                        'remain'    => $this->player->getGoods($cost['gid']),
                        'tree'      => TreeService::getInstance()->getShowTree($this->player),
                    ];

                }
            }
        }
        $this->sendMsg( $result );
    }

}