<?php
namespace App\Api\Controller\LifetimeCard;
use App\Api\Table\ConfigParam;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\TicketService;
use App\Api\Service\Module\LifetimeCardService;

class Buy extends BaseController
{

    public function index()
    {
        $result = '以激活终身卡';
        if(empty($this->player->getArg(Consts::LIFETTIME_CARD_TIME)))
        {
            $discount =  mul(ConfigParam::getInstance()->getFmtParam('DISCOUNT'),19800);
            try {
                $result = '余额不足';
                $balance = TicketService::getInstance($this->player)->getBalance();
                if($balance >= $discount)
                {
                    $payRes = TicketService::getInstance()->pay( $discount );

                    $reward = [
                        ['type' => GOODS_TYPE_1,'gid' => 100000,'num' => 1980 ],
                        ['gid' => 105047,'num' => -$discount,'type' => GOODS_TYPE_1 ],
                    ];
                        
                    $goodsList[] = ['type' => GOODS_TYPE_1,'gid' => 100000,'num' => 1980 ];

                    $desc = '购买终身卡'.$payRes['bill_no'].' '.$balance.' =>'.$payRes['balance'];
                    $this->player->goodsBridge($reward,'扣除券',$desc);

                    $this->player->setArg(Consts::LIFETTIME_CARD_TIME,time(),'reset');
                    LifetimeCardService::getInstance()->lifetimeCardEmail($this->player);
        
                    $result = [
                        'lifetimeCard' => LifetimeCardService::getInstance()->getLifetimeCardFmtData($this->player),
                        'reward'       => $goodsList,
                        'ticket'       => $payRes['balance'],
                    ];

                }
            } catch (\Throwable $th) {
                $result = $th->getMessage();
            }


        }
        $this->sendMsg( $result );
    }

}