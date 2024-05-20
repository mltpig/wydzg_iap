<?php
namespace App\Api\Controller\Spirit;
use App\Api\Table\ConfigParam;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\SpiritService;
use App\Api\Service\TaskService;

class Draw extends BaseController
{

    public function index()
    {
        // $param['num'] = 1 | 5 单抽 | 五连
        $param = $this->param;

        $cost = ConfigParam::getInstance()->getFmtParam('SPIRIT_PULL_COST');

        if($param['isAd']){
            $num = $cost['num'] * $param['num'];
            $freeCount  = ConfigParam::getInstance()->getFmtParam('SPIRIT_AD_LIMIT');

            $result = '今日次数已达上限';
            if($freeCount > $this->player->getArg( Consts::SPIRIT_AD_TAG )){

                $this->player->setArg(Consts::SPIRIT_DRAW_COUNT,1,'add'); // 累计抽奖次数
                list($spiritId,$spiritNum,$compoundItemId) = SpiritService::getInstance()->getSpiritRandom($this->player->getArg( Consts::SPIRIT_DRAW_COUNT ));
                
                $reward = [ [ 'type' => GOODS_TYPE_1,'gid' => $compoundItemId,'num' => $spiritNum ] ];
                $this->player->goodsBridge($reward,'红颜抽卡','1' );

                $award[] = [$compoundItemId => $spiritNum];

                $this->player->setArg(Consts::SPIRIT_AD_TAG,1,'add');
                TaskService::getInstance()->setVal($this->player,34,$num,'add');

                $result = [
                    'isAd'   => $param['isAd'],
                    'remain' => $freeCount - $this->player->getArg( Consts::SPIRIT_AD_TAG ) ,
                    'spirit' => SpiritService::getInstance()->getSpiritFmtData( $this->player, $this->player->getArg( Consts::SPIRIT_AD_TAG )),
                    'reward' => $award
                ];
            }
        }else{
            $num = $cost['num'] * $param['num'];
            
            $result = "道具不足";
            $hasNum = $this->player->getGoods($cost['gid']);
            if($hasNum >= $num){

                $reward = [];
                for($i=1; $i <= $param['num']; $i++){
                    $this->player->setArg(Consts::SPIRIT_DRAW_COUNT,1,'add'); // 累计抽奖次数
                    list($spiritId,$spiritNum,$compoundItemId) = SpiritService::getInstance()->getSpiritRandom($this->player->getArg( Consts::SPIRIT_DRAW_COUNT ));

                    $reward[] = [ 'type' => GOODS_TYPE_1,'gid' => $compoundItemId,'num' => $spiritNum ];
                    $award[] = [$compoundItemId => $spiritNum];
                }

                $reward[] = [ 'type' => GOODS_TYPE_1,'gid' => $cost['gid'],'num' => -$num ];
                $this->player->goodsBridge($reward,'红颜抽卡',$hasNum );


                TaskService::getInstance()->setVal($this->player,34,$num,'add');

                $result = [
                    'isAd'   => $param['isAd'],
                    'remain' => $this->player->getGoods($cost['gid']),
                    'spirit' => SpiritService::getInstance()->getSpiritFmtData( $this->player, $this->player->getArg( Consts::SPIRIT_AD_TAG )),
                    'reward' => $award
                ];
            }
        }

        $this->sendMsg( $result );
    }

}