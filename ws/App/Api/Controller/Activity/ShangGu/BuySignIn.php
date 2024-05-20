<?php
namespace App\Api\Controller\Activity\ShangGu;
use App\Api\Utils\Consts;
use App\Api\Table\ConfigParam;
use App\Api\Service\Module\ShangGuService;
use App\Api\Service\Module\TicketService;
use App\Api\Controller\BaseController;

class BuySignIn extends BaseController
{

    public function index()
    {
        $result = '福利已购买';
        if(empty($this->player->getArg(Consts::SHANGGU_SIGNIN_GIFT)))
        {
            $discount =  mul(ConfigParam::getInstance()->getFmtParam('DISCOUNT'),3000);
            try {
                $result = '余额不足';
                $balance = TicketService::getInstance($this->player)->getBalance();
                if($balance >= $discount)
                {
                    $payRes = TicketService::getInstance()->pay( $discount );

                    $reward = [
                        ['gid' => 105047,'num' => -$discount,'type' => GOODS_TYPE_1 ],
                    ];
                        
                    $desc = '购买商贾签到礼包'.$payRes['bill_no'].' '.$balance.' =>'.$payRes['balance'];
                    $this->player->goodsBridge($reward,'扣除券',$desc);

                    $this->player->setArg(Consts::SHANGGU_SIGNIN_GIFT,time(),'reset');

                    $result = [
                        'shanggu'     => ShangGuService::getInstance()->getShangGuFmtData($this->player),
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