<?php
namespace App\Api\Controller\Fund;
use App\Api\Table\ConfigParam;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\TicketService;
use App\Api\Service\Module\FundService;

class Buy extends BaseController
{

    public function index()
    {
        $param       = $this->param;
        $group       = $param['group'];
        $tag         = FundService::getInstance()->getGroupWhereArg($group);
        $consumption = [1 => 9800,2 => 64800,3 => 6800,4 => 32800];

        $result = '该基金已购买';
        if(empty($this->player->getArg($tag)))
        {
            $discount =  mul(ConfigParam::getInstance()->getFmtParam('DISCOUNT'),$consumption[$group]);
            try {
                $result = '余额不足';
                $balance = TicketService::getInstance($this->player)->getBalance();
                if($balance >= $discount)
                {
                    $payRes = TicketService::getInstance()->pay( $discount );

                    $reward = [
                        ['gid' => 105047,'num' => -$discount,'type' => GOODS_TYPE_1 ],
                    ];
                        
                    $desc = '购买基金'.$payRes['bill_no'].' '.$balance.' =>'.$payRes['balance'];
                    $this->player->goodsBridge($reward,'扣除券',$desc);

                    $this->player->setArg($tag,time(),'reset');

                    $result = [
                        'fund'        => FundService::getInstance()->getFundGroupData($this->player,$group),
                        'config'      => [
                            'state' => $this->player->getArg($tag),
                        ],
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