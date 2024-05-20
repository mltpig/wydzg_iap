<?php
namespace App\Api\Controller\Activity\XianYuan;
use App\Api\Utils\Consts;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigShop;
use App\Api\Service\Module\XianYuanService;
use App\Api\Service\Module\TicketService;
use App\Api\Service\Module\FundService;
use App\Api\Controller\BaseController;

class BuyGift extends BaseController
{

    public function index()
    {   
        $param = $this->param;
        $config = ConfigShop::getInstance()->getOne($param['id']);

        $result   = '购买礼包今日已上限';
        if($config['buy_limit'] > $this->player->getArg($param['id']) )
        {
            $discount =  mul(ConfigParam::getInstance()->getFmtParam('DISCOUNT'),$config['price']['num']);
            try {
                $result = '余额不足';
                $balance = TicketService::getInstance($this->player)->getBalance();
                $reward = $goodsList = [];
                if($balance >= $discount)
                {
                    $payRes = TicketService::getInstance()->pay( $discount );

                    $reward  = $goodsList = $config['reward'];
                    $reward[] = ['gid' => 105047,'num' => -$discount,'type' => GOODS_TYPE_1 ];
                        
                    $desc = '购买仙缘礼包'.$payRes['bill_no'].' '.$balance.' =>'.$payRes['balance'];
                    $this->player->goodsBridge($reward,'扣除券',$desc);

                    $this->player->setArg($param['id'],1,'add');
                    $this->player->setArg(Consts::XIANYUAN_GIFT_SCHEDULE,1,'add');

                    $result = [
                        'xianyuan' => XianYuanService::getInstance()->getXianYuanFmtData($this->player),
                        'reward'   => $goodsList,
                        'ticket'   => $payRes['balance'],
                    ];
                }
            } catch (\Throwable $th) {
                $result = $th->getMessage();
            }
        }
        $this->sendMsg( $result );
    }

}