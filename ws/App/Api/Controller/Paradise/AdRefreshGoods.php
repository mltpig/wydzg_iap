<?php
namespace App\Api\Controller\Paradise;
use App\Api\Controller\BaseController;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigParadiseReward;
use App\Api\Service\ParadisService;

//刷新自己物资
class AdRefreshGoods extends BaseController
{

    public function index()
    {

        $goodsList = $this->player->getData('paradise')['list'];
        $limit = ConfigParam::getInstance()->getFmtParam('HOMELAND_FREE_REFRESH_TIME');

        $result = '数量不足';
        if($limit > $this->player->getArg(PARADISE_AD_REFRES_GOODS))
        {
            $isProtect = true;

            foreach ($goodsList as $pos => $value) 
            {
                $collect = $value['player'];
                if($value['player'] && (isset($collect['a']['uid'])  || isset($collect['g']['uid']))) continue;

                //保护，五颗星
                if($isProtect)
                {
                    $gid   = ConfigParadiseReward::getInstance()->getReward(5);
                    $newGoods =   [ 'gid' => $gid,'player' => [],'time' => time() + 120,'type' => 2 ,'exp' => 0,'drift' => 0];
                    $isProtect = false;
                }else{
                    $newGoods = ParadisService::getInstance()->getRandGoods();
                }

                $this->player->setParadise('list','pos',$pos,$newGoods,'set');
            }

            $this->player->setArg(PARADISE_AD_REFRES_GOODS,1,'add');

            $result = ParadisService::getInstance()->getShowData( $this->player );

        }

        $this->sendMsg( $result );
    }

}