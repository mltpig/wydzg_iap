<?php
namespace App\Api\Service\Pay\Wx;
use App\Api\Utils\Request;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\Log\LoggerInterface;
use EasySwoole\Utility\SnowFlake;
use App\Api\Service\Channel\WeixinService;
use EasySwoole\Component\CoroutineSingleTon;

class VoucherService
{
    use CoroutineSingleTon;
    
    private $offerId      = null;
    private $appKey       = null;
    private $env          = null;
    private $sessionKey   = null;
    private $accessToken  = null;
    private $openid       = '';
    private $site         = '';

    public function __construct(string $openid,int $site)
    {
        $this->site        = strval($site);
        //$this->site        = '1';//调试写死
        $this->openid      = $openid;
        $this->env         = MidasService::getInstance()->getEnv();
        $this->offerId     = MidasService::getInstance()->getOfferId();
        $this->accessToken = WeixinService::getInstance()->getAccessToken();
    }

    public function getBalance():int
    {
    
        $query = [ 'access_token'  => $this->accessToken,  'sig_method'    => 'hmac_sha256' ];

        $body = [
            "openid"    =>  $this->openid,
            "offer_id"  =>  $this->offerId,
            "ts"        =>  time(),
            "zone_id"   =>  $this->site,
            "env"       =>  $this->env,
        ];
        $json = json_encode($body);
        $query['pay_sig']   = MidasService::getInstance()->createPaySign('/wxa/game/getbalance&' .$json);
        $query['signature'] = MidasService::getInstance()->createLoginSign($body);

        $url = 'https://api.weixin.qq.com/wxa/game/getbalance?'.http_build_query($query);
        list($result,$reBody) = Request::getInstance()->http( $url,'post',$body);
        if(!is_array($result))
        {
            Logger::getInstance()->log('getbalance error:'.$reBody.' query : '.$url.'; post: '.$json,LoggerInterface::LOG_LEVEL_ERROR,'mds_getbalance');
            throw new \Exception('获取余额失败');
        } 
        
        if($result['errcode'])
        {
            Logger::getInstance()->log('getbalance error:'.$reBody.' query : '.$url.'; post: '.$json,LoggerInterface::LOG_LEVEL_ERROR,'mds_getbalance');
            throw new \Exception($result['errmsg']);
        } 

        return $result['balance'];
    }

    public function pay(int $amount):array
    {
        $query = [ 'access_token'  => $this->accessToken,  'sig_method'    => 'hmac_sha256' ];
        $body = [
            "openid"    =>  $this->openid,
            "offer_id"  =>  $this->offerId,
            "ts"        =>  time(),
            "zone_id"   =>  $this->site,
            "env"       =>  $this->env,
            "amount"    =>  $amount,
            "bill_no"   =>  strval(SnowFlake::make(rand(0,31),rand(0,127))),
        ];
        $json = json_encode($body);
        $query['pay_sig']   = MidasService::getInstance()->createPaySign('/wxa/game/pay&' .$json);
        $query['signature'] = MidasService::getInstance()->createLoginSign($body);

        $url = 'https://api.weixin.qq.com/wxa/game/pay?'.http_build_query($query);
        list($result,$resBody) = Request::getInstance()->http( $url,'post',$body);

        if(!is_array($result))
        {
            Logger::getInstance()->log('pay error:'.$resBody.' query : '.$url.'; post: '.$json,LoggerInterface::LOG_LEVEL_ERROR,'mds_pay');
            throw new \Exception('扣除余额失败');
        } 
        
        if($result['errcode'])
        {
            Logger::getInstance()->log('pay error:'.$resBody.' query : '.$url.'; post: '.$json,LoggerInterface::LOG_LEVEL_ERROR,'mds_pay');

            if($result['errcode'] == -1){
                throw new \Exception('系统繁忙');
            }elseif ($result['errcode'] ==90000 ||$result['errcode'] ==90010 ||$result['errcode'] ==90011 ||$result['errcode'] ==90012 ){
                throw new \Exception('订单错误');
            }elseif ($result['errcode'] ==90013  ){
                throw new \Exception('余额不足');
            } else{
                throw new \Exception($result['errmsg']);
            }
        }

        return [ 'balance' => $result['balance'],'bill_no' => $result['bill_no'] ];
    }

    public function present(int $amount):int
    {
        $query = [ 'access_token'  => $this->accessToken,  'sig_method'    => 'hmac_sha256' ];
        $body = [
            "openid"    =>  $this->openid,
            "offer_id"  =>  $this->offerId,
            "ts"        =>  time(),
            "zone_id"   =>  $this->site,
            "env"       =>  $this->env,
            "amount"    =>  $amount,
            "bill_no"   =>  strval(SnowFlake::make(rand(0,31),rand(0,127))),
        ];
        $json = json_encode($body);
        $query['pay_sig']   = MidasService::getInstance()->createPaySign('/wxa/game/present&' .$json);
        $query['signature'] = MidasService::getInstance()->createLoginSign($body);

        $url = 'https://api.weixin.qq.com/wxa/game/present?'.http_build_query($query);
        list($result,$resBody) = Request::getInstance()->http( $url,'post',$body);

        if(!is_array($result))
        {
            Logger::getInstance()->log('present error:'.$resBody.' query : '.$url.'; post: '.$json,LoggerInterface::LOG_LEVEL_ERROR,'mds_present');
            throw new \Exception('赠送余额失败');
        } 
        
        if($result['errcode'])
        {
            Logger::getInstance()->log('present error:'.$resBody.' query : '.$url.'; post: '.$json,LoggerInterface::LOG_LEVEL_ERROR,'mds_present');
            throw new \Exception($result['errmsg']);
        }
        
        return $result['balance'];
    }

    public function queryOrder(string $tradeNo):array
    {
        $query = [ 'access_token'  => $this->accessToken,  'sig_method'    => 'hmac_sha256' ];
        $body = [
            "openid"        =>  $this->openid,
            "offer_id"      =>  $this->offerId,
            "ts"            =>  time(),
            "zone_id"       =>  $this->site,
            "env"           =>  $this->env,
            "out_trade_no"  =>  $tradeNo,
            "biz_id"        =>  1,
        ];

        $json = json_encode($body);

        $query['pay_sig']   = MidasService::getInstance()->createPaySign('/wxa/game/queryorderinfo&' .$json);
        $query['signature'] = MidasService::getInstance()->createLoginSign($body);

        $url = 'https://api.weixin.qq.com/wxa/game/queryorderinfo?'.http_build_query($query);
        list($result,$resBody) = Request::getInstance()->http( $url,'post',$body);

        if(!is_array($result))
        {
            Logger::getInstance()->log('queryOrder error:'.$resBody.' query : '.$url.'; post: '.$json,LoggerInterface::LOG_LEVEL_ERROR,'mds_query');
            throw new \Exception('查询订单失败');
        } 
        
        if($result['errcode'])
        {
            Logger::getInstance()->log('present error:'.$resBody.' query : '.$url.'; post: '.$json,LoggerInterface::LOG_LEVEL_ERROR,'mds_query');
            throw new \Exception($result['errmsg']);
        }
        
        return $result;
    }

}