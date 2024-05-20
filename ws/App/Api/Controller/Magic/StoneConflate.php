<?php
namespace App\Api\Controller\Magic;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigMagic;
use App\Api\Table\ConfigStone;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\MagicService;
use App\Api\Service\TaskService;

class StoneConflate extends BaseController
{

    public function index()
    {
        $param  = $this->param;
        $bag    = $this->player->getData('magic','bag');

        $result = '未衍化';
        if(array_key_exists($param['id'],$bag))
        {
            $config     = ConfigMagic::getInstance()->getOne($param['id']);
            $stone_type = explode("|",$config['stone_type']);
            $compound   = ConfigStone::getInstance()->getCompoundItem($stone_type);

            $awards = [];
            foreach($compound as $id => $val)
            {
                $num        =   $this->player->getGoods($val['compound_item_id']);
                $config_num =   intval($num / $val['compound_num']);
    
                if(empty($config_num)) continue;
    
                $reward = [];
                for($i = 0; $i < $config_num; $i++)
                {
                    $reward[] = [ 'type' => GOODS_TYPE_23, 'gid' => $val['compound_item_id'], 'num' => -$val['compound_num'] ];
                    $reward[] = $awards[] = [ 'type' => GOODS_TYPE_23, 'gid' => $id, 'num' => 1];
                }
                $this->player->goodsBridge($reward,'合成刻印');
            }

            if($awards)
            {
                $result = [
                    'magic' => MagicService::getInstance()->getMagicFmtData($this->player),
                    'goods' => $this->player->getGoodsInfo(),
                    'reward'=> MagicService::getInstance()->aggregateReward($awards),
                ];
            }else{
                $result = '没有刻印可以融合';
            }
        }
        
        $this->sendMsg( $result );
    }

}