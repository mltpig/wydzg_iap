<?php
namespace App\Api\Controller\Pay\Wx;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\TicketService;

//升级
class Query extends BaseController
{

    public function index()
    {
        $outTradeNo  = $this->param['outTradeNo'];

        try {

            TicketService::getInstance($this->player)->queryOrder($outTradeNo);

            $result = [ 
                'ticket' => TicketService::getInstance()->getBalance(),
            ];
            
        } catch (\Throwable $th) {

            $result = $th->getMessage();
        }

        $this->sendMsg( $result );
    }

}