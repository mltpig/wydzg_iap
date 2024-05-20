<?php
namespace App\Api\Controller\MonthlyCard;
use App\Api\Table\ConfigParam;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\TicketService;
use App\Api\Service\Module\MonthlyCardService;

class Buy extends BaseController
{

    public function index()
    {
        $result = '月卡还未到期';
        if(empty(MonthlyCardService::getInstance()->getMonthlyCardExpire($this->player)))
        {

            $discount =  mul(ConfigParam::getInstance()->getFmtParam('DISCOUNT'),3000);
            try {
                $result = '余额不足';
                $balance = TicketService::getInstance($this->player)->getBalance();
                if($balance >= $discount)
                {
                    $payRes = TicketService::getInstance()->pay( $discount );

                    $reward = [
                        ['type' => GOODS_TYPE_1,'gid' => 100000,'num' => 300 ],
                        ['gid' => 105047,'num' => -$discount,'type' => GOODS_TYPE_1 ],
                    ];

                    $goodsList[] = ['type' => GOODS_TYPE_1,'gid' => 100000,'num' => 300 ];

                        
                    $desc = '购买月卡'.$payRes['bill_no'].' '.$balance.' =>'.$payRes['balance'];
                    $this->player->goodsBridge($reward,'扣除券',$desc);

                    $this->player->setArg(Consts::MONTHLY_CARD_TIME,strtotime("+30 days", time()),'reset');
                    MonthlyCardService::getInstance()->monthlyCardEmail($this->player);
                
                    $result = [
                        'monthlyCard' => MonthlyCardService::getInstance()->getMonthlyCardFmtData($this->player),
                        'reward'      => $goodsList,
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