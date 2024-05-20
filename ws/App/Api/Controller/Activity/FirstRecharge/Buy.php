<?php
namespace App\Api\Controller\Activity\FirstRecharge;

use App\Api\Table\ConfigParam;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\TicketService;
use App\Api\Service\ActivityService;
//首冲
class Buy extends BaseController
{

    public function index()
    { 
        $result = '已领取过该活动奖励';
        if(!$this->player->getArg(COUNTER_FR))
        {
            $discount =  mul(ConfigParam::getInstance()->getFmtParam('DISCOUNT'),600);
            try {

                $result = '余额不足';
                $balance = TicketService::getInstance($this->player)->getBalance();
                if($balance >= $discount)
                {
                    $payRes = TicketService::getInstance()->pay( $discount );

                    $desc = '首冲奖励'.$payRes['bill_no'].' '.$balance.' =>'.$payRes['balance'];
                    $this->player->goodsBridge([['gid' => 105047,'num' => -$discount,'type' => GOODS_TYPE_1 ]],'扣除券',$desc);

                    $this->player->setArg(COUNTER_FR,time(),'reset');

                    $result = [ 
                        'list'   => ActivityService::getInstance()->getFirstRechargeConfig($this->player),
                        'ticket' => $payRes['balance'],
                     ];

                }
            } catch (\Throwable $th) {
                $result = $th->getMessage();
            }
        }
        
        $this->sendMsg( $result );
    }

}