<?php
namespace App\Api\Controller\Pay\Wx;
use App\Api\Controller\BaseController;
use App\Api\Model\PayOrder;
use App\Api\Table\ConfigPaid;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\Log\LoggerInterface;
use EasySwoole\Utility\SnowFlake;
use App\Api\Service\Pay\Wx\MidasService;

//升级
class Order extends BaseController
{

    public function index()
    {
        $rechargeid  = $this->param['rechargeId'];

        $result = '无效的充值ID';
        $config = ConfigPaid::getInstance()->getOne($rechargeid);
        if($config)
        {
            $openid = $this->player->getData('openid');
            $orderInfo = [
                'order_id'    => strval(SnowFlake::make(rand(0,31),rand(0,127))),
                'recharge_id' => $rechargeid,
                'openid'      => $openid,
                'site'        => $this->player->getData('site'),
                'state'       => 0,
                'create_time' => date('Y-m-d H:i:s'),
            ];
            
            $result = '服务器繁忙，请稍后再试';
            try {
                if( PayOrder::create($orderInfo)->save() )
                {
                    $result = [ 
                        'orderInfo' => [
                            'mode' 	        => 'game',
                            'env' 	        => 1,
                            'offerId'       => MidasService::getInstance()->getOfferId(),
                            'currencyType'  => 'CNY',
                            'platform'      => 'android',
                            'buyQuantity'   => $config['repeat_reward']['num'],
                            'zoneId'        => strval(1),
                            // 'zoneId'        => strval($this->player->getData('site')),
                            'outTradeNo'    => $orderInfo['order_id'],
                        ]
                    ];
                }

            } catch (\Throwable $th) {
                Logger::getInstance()->log('pay error:'.$th->getMessage(),LoggerInterface::LOG_LEVEL_ERROR,'error');
            }

        }

        $this->sendMsg( $result );
    }

}