<?php
namespace App\Api\Service\Pay\Wx;
use EasySwoole\Component\CoroutineSingleTon;

class MessageService
{
    use CoroutineSingleTon;
    
    private $token            = 'R3WJkdLCiF0gSG74qBHYEoWWhZe5YcIe';
    private $EncodingAESKey   = 'rtEyzsGpHPxAjHnTeDyD1AM7MXi4bvB8NLlZP0xdfmO';

    public function firstCheck(array $param):string
    {

        $echostr   =  array_key_exists('echostr',$param) ? $param["echostr"] : '';
        $signature =  array_key_exists('signature',$param) ? $param["signature"] : '';

        return $signature === $this->createSign($param,$this->token) ? $echostr : 'signature error';
    }

    public function createSign(array $param):string
    {
        $nonce     =  array_key_exists('nonce',$param) ? $param["nonce"] : '';
        $timestamp =  array_key_exists('timestamp',$param) ? $param["timestamp"] : '';
    
        $tmpArr = [ $this->token , $timestamp, $nonce ];
        
        sort($tmpArr, SORT_STRING);

        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
    
        return $tmpStr;
    }

    function run(\swoole_http_request $request):string
    {
        $get  = $request->get ? $request->get : [];

        if( $this->firstCheck( $get ) ) return json_encode(["ErrCode" => 99999,"ErrMsg" => "signature"],273);

        $param = json_decode($request->getContent(),true);
        if(!is_array($param)) return json_encode(["ErrCode" => 99999,"ErrMsg" => "body 非json格式"],273);
        if(!array_key_exists('Event',$param) || !array_key_exists('MiniGame',$param) ) return json_encode(["ErrCode" => 99999,"ErrMsg" => "body 参数遗漏"],273);
        
        $result = '';
        switch ($param['Event']) 
        {
            case 'minigame_deliver_goods':
                $result = GameGiftService::getInstance()->send($param['MiniGame']);
            break;
        }
        return $result;
    }


}