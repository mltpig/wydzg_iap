<?php
namespace App\Api\Service\Channel;

use App\Api\Utils\Request;
use EasySwoole\Component\CoroutineSingleTon;
use EasySwoole\EasySwoole\Logger;

class WeixinService
{
    use CoroutineSingleTon;

    private $appid  = 'wx3878aa286c71eebd';
    private $secret = 'e2e7e6637b66528c5fc794a4d18e29f8';

    public function getUserInfo(string $code)
    {
        $api  = 'https://api.weixin.qq.com/sns/jscode2session';
        
        $param =  array(
            'appid'      => $this->appid,
            'secret'     => $this->secret,
            'js_code'    => $code,
            'grant_type' => 'authorization_code',
        );

        list($result,$body) = Request::getInstance()->http($api,'get',$param);

        if(is_array($result) && isset($result['openid']))
        {
            $data  = [ 
                'openid'      => $result['openid'],
                'session_key' => $result['session_key'],
            ];
        }else{
            Logger::getInstance()->log("URL: ".$api.' === param: '.json_encode($param,JSON_UNESCAPED_UNICODE)." === body: ".$body);

            $data  = $result['errmsg'];
        }

        return $data;
    }

    public function getDecryptData(string $sessionKey,string $iv,string $encryptedData)
    {
        if (strlen($sessionKey) != 24) return 'sessionKey 非法';
        if (strlen($iv) != 24) return 'iv 非法';
    
        $aesIV     = base64_decode($iv);
        $aesKey    = base64_decode($sessionKey);
        $aesCipher = base64_decode($encryptedData);
    
        $result = json_decode(openssl_decrypt( $aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV),true);

        if( is_null($result ) ) return '解密失败';

        if( $result['watermark']['appid'] != $this->appid ) return 'appid不符合';
        
        $list = [];
        foreach ($result['dataList'] as $key => $value) 
        {
            $list[ $value['dataType']['type'] ] = $value['value'];
        }

        return $list;
    
    }

}