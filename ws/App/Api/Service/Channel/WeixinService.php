<?php
namespace App\Api\Service\Channel;

use App\Api\Utils\Consts;
use App\Api\Utils\Request;
use EasySwoole\Component\CoroutineSingleTon;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\Log\LoggerInterface;

class WeixinService
{
    use CoroutineSingleTon;

    private $appid  = 'wx3878aa286c71eebd';
    private $token  = '';

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

    public function msgSecCheck(string $openid,string $content):bool
    {

        $token = $this->getAccessToken();

        $api = 'https://api.weixin.qq.com/wxa/msg_sec_check?access_token='.$token;

        $param = [ 'version' => 2,'openid' => $openid,'scene' => 2,'content' => $content ];

        list($result,$body) = Request::getInstance()->http($api,'post',$param);

        if(is_array($result) && !$result['errcode'] )
        {
            if($result['result']['suggest'] === 'pass')
            {
                return true;
            }else{
                Logger::getInstance()->log("关键词检测结果异常: ".$api.' === param: '.json_encode($param,JSON_UNESCAPED_UNICODE)." === body: ".$body);
            }
        }else{
            Logger::getInstance()->log("关键词返回异常: ".$api.' === param: '.json_encode($param,JSON_UNESCAPED_UNICODE)." === body: ".$body);
        }
        
        return false;

    }

    public function getAccessToken():string
    {
        if($this->token) return $this->token;

        $api = 'https://ysjdftz.shenzhenyuren.com/wx_token/get';

        $param = [ 
            'tag'       => encrypt('dividendr3WSzC7ZxJ',Consts::AES_KEY,Consts::AES_IV) ,
            'code'      => encrypt('bA6FjyenSbPBsfAaT5x5',Consts::AES_KEY,Consts::AES_IV),
            'timestamp' => time()
        ];

        $param['sign'] = $this->createSign($param,'vfxloCPx2oGssv7qqXekl1D7U3cKj2TN');

        list($result,$body) = Request::getInstance()->http($api,'post',$param);
        
        if($result['code'])
        {
            Logger::getInstance()->log("获取token: ".$api.' === param: '.json_encode($param,JSON_UNESCAPED_UNICODE)." === body: ".$body,LoggerInterface::LOG_LEVEL_ERROR,'getAccess');
            $this->token = '';
        }else{
            $this->token = decrypt($result['data']['token'],Consts::AES_KEY,Consts::AES_IV);
        }
        
        return $this->token;
    }

    public function createSign(array $param,string $secret):string
    {
        ksort($param);
        $str = '';
        foreach ($param as $key => $val) 
        {
            if($key === 'sign' || is_array($val)) continue;
            $str .= $key.'='.$val.'&';
        }
        return strtolower(md5($str.$secret)) ;
    }

}