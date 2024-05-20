<?php
namespace App\Api\Controller\Doufa;
use App\Api\Table\ConfigParam;
use App\Api\Service\DoufaService;
use App\Api\Controller\BaseController;
//回收
class Refresh extends BaseController
{

    public function index()
    {
        
        $config = ConfigParam::getInstance()->getFmtParam('PVP_REFRESH_COST');
        $num    = $this->player->getGoods($config['gid']);
        $result = '数量不足';

        if($num >= $config['num'])
        {
            $cost = [ [ 'type' => GOODS_TYPE_1,'gid' => $config['gid'],'num' => -$config['num'] ] ];
            $this->player->goodsBridge($cost,'斗法刷新',$num);

            $enemy = DoufaService::getInstance()->getEnemysUid($this->param['uid'],$this->param['site'],5);
            $this->player->setData('doufa','enemy',$enemy);

            $result = [ 
                'remain' => $this->player->getGoods($config['gid']), 
                'enemys' => DoufaService::getInstance()->getEnemyList($enemy,$this->param['site']), 
            ];
        }
        
        $this->sendMsg($result);
    }

}