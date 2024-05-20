<?php
namespace App\Api\Controller\Dongfu\Comrade;
use App\Api\Table\ConfigComrade;
use App\Api\Service\ComradeService;
use App\Api\Controller\BaseController;
//升级
class Unlock extends BaseController
{
    
    public function index()
    {
        $id      = $this->param['id'];
        $comrades = $this->player->getData('comrade');
        $result = '未激活';
        if(array_key_exists($id,$comrades) )
        {
            $comrade = $comrades[$id];
            $result = '已解锁';
            if(!$comrade['state'])
            {
                            
                $cost = ConfigComrade::getInstance()->getOne($id)['cost_id'];

                if($cost)
                {
                    $cost = [ [ 'type' => GOODS_TYPE_1,'gid' => $cost['gid'],'num' => -$cost['num'] ] ];
                    $this->player->goodsBridge($cost,'贤士解锁',$id);
                } 

                $this->player->setComrade($id,'state',1,'set');
                $this->player->setComrade($id,'lv',1,'set');

                $comrade = $this->player->getData('comrade');
                list($_sum , $attrSum) = ComradeService::getInstance()->getComradeAttrSum($comrade);
                $result = [
                    'list'         => ComradeService::getInstance()->getShowData($this->player,$comrade),
                    'attr_sum'     => $attrSum,
                    'comrade_need' => ComradeService::getInstance()->getNeedGoods($this->player),
                    'goods'        => $this->player->getGoodsInfo(),
                ];
            }
        }


        $this->sendMsg( $result );
    }

}