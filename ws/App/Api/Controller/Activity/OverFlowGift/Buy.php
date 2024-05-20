<?php
namespace App\Api\Controller\Activity\OverFlowGift;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigShop;
use App\Api\Service\Module\TicketService;
use App\Api\Service\ShopService;
use App\Api\Controller\BaseController;

class Buy extends BaseController
{

    public function index()
    {   
        $param = $this->param;
        $config = ConfigShop::getInstance()->getOne($param['id']);

        $result   = '购买礼包今日已上限';
        if($config['buy_limit'] > $this->player->getArg($param['id']))
        {
            if($config['price']['num'] == 0)
            {
                $reward = [];
                $reward = $config['reward'];
                $this->player->goodsBridge($reward,'免费超值礼包');

                $this->player->setArg($param['id'],1,'add');

                $result = [
                    '102'       => ShopService::getInstance()->getShowList($this->player,102),
                    '103'       => ShopService::getInstance()->getShowList($this->player,103),
                    '104'       => ShopService::getInstance()->getShowList($this->player,104),
                    'reward'    => $reward,
                ];
            }else{
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
                            
                        $desc = '购买超值礼包'.$payRes['bill_no'].' '.$balance.' =>'.$payRes['balance'];
                        $this->player->goodsBridge($reward,'扣除券',$desc);

                        $this->player->setArg($param['id'],1,'add');

                        $result = [
                            '102'       => ShopService::getInstance()->getShowList($this->player,102),
                            '103'       => ShopService::getInstance()->getShowList($this->player,103),
                            '104'       => ShopService::getInstance()->getShowList($this->player,104),
                            'reward'    => $goodsList,
                            'ticket'    => $payRes['balance'],
                        ];
                    }
                } catch (\Throwable $th) {
                    $result = $th->getMessage();
                }
            }
        }
        $this->sendMsg( $result );
    }

}