<?php
namespace App\Api\Controller\Magic;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigMagic;
use App\Api\Table\ConfigMagicLevelUp;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\MagicService;
use App\Api\Service\TaskService;

class Reset extends BaseController
{

    public function index()
    {
        $param = $this->param;
        $magic   = $this->player->getData('magic');

        $result = '未衍化';
        if(array_key_exists($param['id'],$magic['bag']))
        {
            $old = $magic['bag'][$param['id']];
            $result = '无需重置';
            if($old['lv'] > 1)
            {
                $config = ConfigParam::getInstance()->getFmtParam('MAGIC_RESET_COST');
                $result = '道具不足';
                if($this->player->getGoods($config['gid']) >= $config['num'])
                {
                    $lv_spend   = $old['lv'] - 1;

                    $this->player->goodsBridge([ ['type' => GOODS_TYPE_1, 'gid' => $config['gid'], 'num' => -$config['num']] ],'神通重置消耗');

                    $awards     = ConfigMagicLevelUp::getInstance()->getLvSpend($lv_spend);
                    foreach($awards as $moppingupAward)
                    {
                        $this->player->goodsBridge($moppingupAward['cost'],'神通重置回收奖励');
                    }

                    $old['lv'] = 1;
                    $this->player->setMagic('bag',$param['id'],$old,'multiSet');

                    $reward = MagicService::getInstance()->aggregateAwards($awards);

                    $result = [
                        'magic'     => MagicService::getInstance()->getMagicFmtData($this->player),
                        'reward'    => $reward,
                        'remain'    => $this->player->getGoods($config['gid']),
                    ];
                }
            }
        }
        $this->sendMsg( $result );
    }

}