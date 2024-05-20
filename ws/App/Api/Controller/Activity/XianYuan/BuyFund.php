<?php
namespace App\Api\Controller\Activity\XianYuan;
use App\Api\Utils\Consts;
use App\Api\Table\ConfigParam;
use App\Api\Service\Module\XianYuanService;
use App\Api\Service\Module\TicketService;
use App\Api\Service\Module\FundService;
use App\Api\Controller\BaseController;

class BuyFund extends BaseController
{

    public function index()
    {
        $result = '福利已购买';
        if(empty($this->player->getArg(Consts::XIANYUAN_FUND_GROUP9)))
        {
            $discount =  mul(ConfigParam::getInstance()->getFmtParam('DISCOUNT'),6800);
            try {
                $result = '余额不足';
                $balance = TicketService::getInstance($this->player)->getBalance();
                if($balance >= $discount)
                {
                    $payRes = TicketService::getInstance()->pay( $discount );

                    $reward = [
                        ['gid' => 105047,'num' => -$discount,'type' => GOODS_TYPE_1 ],
                    ];
                        
                    $desc = '购买仙缘基金'.$payRes['bill_no'].' '.$balance.' =>'.$payRes['balance'];
                    $this->player->goodsBridge($reward,'扣除券',$desc);

                    $this->player->setArg(Consts::XIANYUAN_FUND_GROUP9,time(),'reset');

                    $result = [
                        'xianyuan' => XianYuanService::getInstance()->getXianYuanFmtData($this->player),
                        'reward'      => $reward,
                        'ticket'      => $payRes['balance'],
                    ];
                }
            } catch (\Throwable $th) {
                $result = $th->getMessage();
            }
        }
        $this->sendMsg( $result );
    }

}