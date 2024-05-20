<?php
namespace App\Api\Controller\Paradise;
use App\Api\Controller\BaseController;
use App\Api\Table\ConfigParam;
use App\Api\Service\ParadisService;

//刷新自己物资
class RefreshGoods extends BaseController
{

    public function index()
    {

        $goodsList = $this->player->getData('paradise')['list'];
        $cost = ConfigParam::getInstance()->getFmtParam('HOMELAND_PAY_REFRESH_COST');

        $result = '数量不足';
        $has = $this->player->getGoods($cost['gid']);
        if( $has > $cost['num'] )
        {

            foreach ($goodsList as $pos => $value) 
            {
                if($value['player']) continue;

                $newGoods = ParadisService::getInstance()->getRandGoods();

                $this->player->setParadise('list','pos',$pos,$newGoods,'set');
            }

            $costList = [ [ 'type' => GOODS_TYPE_1,'gid' => $cost['gid'],'num' => -$cost['num'] ] ];
            $this->player->goodsBridge($costList,'福地刷新物资',$has);

            $result = ParadisService::getInstance()->getShowData( $this->player );
            $result['remain'] = $this->player->getGoods($cost['gid']);
        }

        $this->sendMsg( $result );
    }

}