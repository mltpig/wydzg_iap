<?php
namespace App\Api\Controller\YuanBao;
use App\Api\Utils\Consts;
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

        $discount =  mul(ConfigParam::getInstance()->getFmtParam('DISCOUNT'),$config['price']['num']);
        try {
            $result = '余额不足';
            $balance = TicketService::getInstance($this->player)->getBalance();
            $reward = $goodsList = [];
            if($balance >= $discount)
            {
                $payRes = TicketService::getInstance()->pay( $discount );

                foreach($config['reward'] as $k => $v)
                {
                    if(empty($this->player->getArg($param['id']))){
                        $reward[] = $goodsList[] = [ 'gid' => $config['reward'][$k]['gid'], 'type' => $config['reward'][$k]['type'], 'num' => $config['reward'][$k]['num'] * 2];
                    }else{
                        $reward[] = $goodsList[] = [ 'gid' => $config['reward'][$k]['gid'], 'type' => $config['reward'][$k]['type'], 'num' => $config['reward'][$k]['num'] ];
                    }
                }

                $reward[] = ['gid' => 105047,'num' => -$discount,'type' => GOODS_TYPE_1 ];
                $desc = '元宝充值'.$payRes['bill_no'].' '.$balance.' =>'.$payRes['balance'];
                $this->player->goodsBridge($reward,'扣除券',$desc);

                $this->player->setArg($param['id'],1,'add');

                $result = [
                    'list'     => ShopService::getInstance()->getShowList($this->player,10),
                    'reward'   => $goodsList,
                    'ticket'   => $payRes['balance'],
                ];
            }
        } catch (\Throwable $th) {
            $result = $th->getMessage();
        }
        
        $this->sendMsg( $result );
    }

}