<?php
namespace App\Api\Controller\Spirit;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigSpirits;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\SpiritService;
use App\Api\Service\TaskService;

class UpLv extends BaseController
{

    public function index()
    {
        $param = $this->param;
        $id     = $param['id'];
        $bag    = $this->player->getData('spirit','bag');

        $config = ConfigSpirits::getInstance()->getOne($id);
        $result = 'ID 暂未开放';
        if($config)
        {
            $result = '未解锁';
            if(array_key_exists($id,$bag))
            {
                $old    = $bag[$id];
                $result = '未解锁';
                if($bag[$id]['state'] > 0)
                {
                    $result = '已达到顶级';
                    if(60 > $old['lv']) //红颜无配置表示满级写死60
                    {
                        $index  = intval($old['lv'] / 5);
                        $num    = ConfigParam::getInstance()->getFmtParam('SPIRIT_LEVEL_COST_NUM')[$index] + 0;
                        $compound_id = $config['compound_item_id'];
    
                        $result = '数量不足';
                        if($this->player->getGoods($compound_id) >= $num)
                        {
                            $old['lv']++;
    
                            $this->player->setSpirit('bag',$id,$old,'multiSet');
  
                            $reward = [ [ 'type' => GOODS_TYPE_1,'gid' => $compound_id,'num' => -$num ] ];
                            $this->player->goodsBridge($reward,'红颜升级',$id );

                            TaskService::getInstance()->setVal($this->player,57,1,'add');
        
                            $result = [ 
                                'remain' => $this->player->getGoods($compound_id),
                                'spirit' => SpiritService::getInstance()->getSpiritFmtData( $this->player, $this->player->getArg( Consts::SPIRIT_AD_TAG )),
                            ];
                        }
                    }
                }
            }
        }

        $this->sendMsg( $result );
    }

}