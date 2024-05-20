<?php
namespace App\Api\Service\Pay\Wx;
use App\Api\Model\PayOrder;
use App\Api\Service\PlayerService;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\Log\LoggerInterface;
use App\Api\Service\Module\TicketService;
use EasySwoole\Component\TableManager;
use EasySwoole\EasySwoole\ServerManager;
use EasySwoole\Component\CoroutineSingleTon;

class PayCallBack
{
    use CoroutineSingleTon;
    
    private $token            = 'R3WJkdLCiF0gSG74qBHYEoWWhZe5YcIe';
    private $EncodingAESKey   = 'rtEyzsGpHPxAjHnTeDyD1AM7MXi4bvB8NLlZP0xdfmO';

    public function firstCheck(array $param):string
    {
        $echostr   =  array_key_exists('echostr',$param) ? $param["echostr"] : '';
        $signature =  array_key_exists('signature',$param) ? $param["signature"] : '';

        return $signature === MessageService::getInstance()->createSign($param,$this->token) ? $echostr : 'signature error';

    }

    function payCallBack(array $get , string $body):string
    {
        if( $this->firstCheck( $get ) ) return json_encode(["ErrCode" => 99999,"ErrMsg" => "signature"],273);
        
        $param = json_decode($body,true);

        if(!is_array($param)) return json_encode(["ErrCode" => 99999,"ErrMsg" => "body 非json格式"],273);
        if(!array_key_exists('Event',$param) || !array_key_exists('MiniGame',$param) ) return json_encode(["ErrCode" => 99999,"ErrMsg" => "body 参数遗漏"],273);
        if($param['Event'] != 'minigame_coin_deliver_completed') return json_encode(["ErrCode" => 99999,"ErrMsg" => "非预期事件"],273);
        
        if(!isset($param['MiniGame']['Payload']) || !isset($param['MiniGame']['IsMock']) || !isset($param['MiniGame']['PayEventSig']) ) return json_encode(["ErrCode" => 99999,"ErrMsg" => "Payload 参数遗漏"],273);
        
        $sign = MidasService::getInstance()->createPaySign($param['Event'] . '&' . $param['MiniGame']['Payload']);
    
        if($sign !== $param['MiniGame']['PayEventSig'])
        {
            Logger::getInstance()->log('pay calback error: sign 错误'.$body,LoggerInterface::LOG_LEVEL_ERROR,'pay_callback');
            return json_encode(["ErrCode" => 99999,"ErrMsg" => "sign 错误"],273);
        } 


        if(!$param['MiniGame']['IsMock'])
        {
            $payload = json_decode($param['MiniGame']['Payload'],true);
            if(!is_array($payload)) return json_encode(["ErrCode" => 99999,"ErrMsg" => "Payload 错误格式"],273);

            $orderObj = PayOrder::create()->get(['order_id' => $payload['OutTradeNo'] ]);

            if( !$orderObj )
            {
                Logger::getInstance()->log('pay calback error: 无效的订单号'.$body,LoggerInterface::LOG_LEVEL_ERROR,'pay_callback');
                return json_encode(["ErrCode" => 99999,"ErrMsg" => "无效的订单号"],273);
            } 

            if( $orderObj->state ) return json_encode(["ErrCode" => 0,"ErrMsg" => "Success"],273);

            $orderObj->update([
                'channe_order' => isset($payload['WeChatPayInfo']['MchOrderNo']) ? $payload['WeChatPayInfo']['MchOrderNo'] : '',
                'state'        => 1,
                'update_time'  => date('Y-m-d H:i:s'),
            ]);

            $this->ding($orderObj->toArray());
        }
    
    
        return json_encode(["ErrCode" => 0,"ErrMsg" => "Success"],273);
    }

    public function ding(array $orderInfo):void
    {
        $fdInfo = TableManager::getInstance()->get(TABLE_UID_FD)->get($orderInfo['openid']);
        if(!$fdInfo) return;

        $server = ServerManager::getInstance()->getSwooleServer();
        if(!$server->isEstablished($fdInfo['fd'])) return;

        $player = new PlayerService($orderInfo['openid'],$orderInfo['site'],$fdInfo['fd']);

        if(!$player->getData('create_time'))
        {
            Logger::getInstance()->log('ding error: find openid error'.json_encode($orderInfo),LoggerInterface::LOG_LEVEL_ERROR,'ding');
            return ;
        } 

        try {
            
            $balance = TicketService::getInstance($player)->getBalance();
            $data = [
                'code'=> SUCCESS,
                'method'=>'pay_success',
                'data'=> [ 
                    'ticket' => $balance,
                    'rechargeId' => $orderInfo['recharge_id'],
                ]
            ];
    
            $server->push($fdInfo['fd'],json_encode($data,JSON_UNESCAPED_UNICODE|JSON_FORCE_OBJECT));

        } catch (\Throwable $th) {
            Logger::getInstance()->log('ding error:'.$th->getMessage(),LoggerInterface::LOG_LEVEL_ERROR,'ding');
        }

    }

}