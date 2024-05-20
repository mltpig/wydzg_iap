<?php
namespace App\Api\Controller\Magic;
use App\Api\Table\ConfigParam;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\MagicService;
use App\Api\Service\TaskService;

class Draw extends BaseController
{

    public function index()
    {
        // $param['num'] = 0 | 1 | 10
        $param = $this->param;

        if($param['isAd'])
        {
            $freeCount  = ConfigParam::getInstance()->getFmtParam('MAGIC_AD_LIMIT') + 0;
            $result = '今日次数已达上限';
            if($freeCount > $this->player->getArg( Consts::MAGIC_AD_TAG ))
            {
                $this->player->setArg(Consts::MAGIC_AD_TAG,1,'add');
                list($id,$itemId,$itemNum,$itemStone) = MagicService::getInstance()->getMagicRandom($this->player);
                $reward     = [[ 'type' => GOODS_TYPE_8,'gid' => $itemId,'num' => $itemNum ]];
                if($itemNum == 1)
                {
                    $old = ['id' => $id, 'lv' => 1, 'stage' => 1, 'stone' => $itemStone];
                    $this->player->setMagic('bag',$id,$old,'multiSet');
                }else{
                    $this->player->goodsBridge($reward,'衍化神通', '1');
                }
                $result = [
                    'magic'  => MagicService::getInstance()->getMagicFmtData($this->player),
                    'reward' => $reward,
                    'isAd'   => $param['isAd'],
                ];
            }
        }else{
            $config_freeCount = ConfigParam::getInstance()->getFmtParam('MAGIC_PULL_FREE_TIME') + 0; //免费衍化神通
            if($config_freeCount - $this->player->getArg( Consts::MAGIC_FREE_COUNT ))
            {
                $this->player->setArg(Consts::MAGIC_FREE_COUNT,1,'add');
                list($id,$itemId,$itemNum,$itemStone) = MagicService::getInstance()->getMagicRandom($this->player);
                $reward     = [[ 'type' => GOODS_TYPE_8,'gid' => $itemId,'num' => $itemNum ]];
                if($itemNum == 1)
                {
                    $old = ['id' => $id, 'lv' => 1, 'stage' => 1, 'stone' => $itemStone];
                    $this->player->setMagic('bag',$id,$old,'multiSet');
                }else{
                    $this->player->goodsBridge($reward,'衍化神通', '1');
                }
                $result = [
                    'magic'  => MagicService::getInstance()->getMagicFmtData($this->player),
                    'reward' => $reward,
                    'isAd'   => $param['isAd'],
                ];
            }else{
                $cost       = ConfigParam::getInstance()->getFmtParam('MAGIC_PULL_COST');
                $cost_num   = $cost['num'] * $param['num'];
                $result = "道具不足";
                if($this->player->getGoods($cost['gid']) >= $cost_num)
                {
                    for($i=1; $i <= $param['num']; $i++)
                    {
                        list($id,$itemId,$itemNum,$itemStone) = MagicService::getInstance()->getMagicRandom($this->player);
                        $reward     = [[ 'type' => GOODS_TYPE_8,'gid' => $itemId,'num' => $itemNum ]];
                        $award[]    = [ 'type' => GOODS_TYPE_8,'gid' => $itemId,'num' => $itemNum ];
                        if($itemNum == 1)
                        {
                            $old = ['id' => $id, 'lv' => 1, 'stage' => 1, 'stone' => $itemStone];
                            $this->player->setMagic('bag',$id,$old,'multiSet');
                        }else{
                            $this->player->goodsBridge($reward,'衍化神通', $this->player->getGoods($itemId));
                        }
                    }
                    $reduce[] = [ 'type' => GOODS_TYPE_1, 'gid' => $cost['gid'], 'num' => -$cost_num ];
                    $this->player->goodsBridge($reduce,'衍化神通', $this->player->getGoods($cost['gid']));

                    $result = [
                        'magic'  => MagicService::getInstance()->getMagicFmtData($this->player),
                        'reward' => $award,
                        'remain' => $this->player->getGoods($cost['gid']),
                        'isAd'   => $param['isAd'],
                    ];
                }
            }
        }

        $this->sendMsg( $result );
    }

}