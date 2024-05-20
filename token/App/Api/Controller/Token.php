<?php
namespace App\Api\Controller;

use App\Api\Service\WeixinService;
use App\Api\Service\YinliTokenService;
use EasySwoole\Http\AbstractInterface\Controller;

class Token extends Controller
{

    public function index()
    {
        $param     = $this->request()->getRequestParam();
        var_dump($param);

        $sign      = array_key_exists('gravity_sign',$param) ? $param['gravity_sign'] : '';
        $timestamp = array_key_exists('gravity_timestamp',$param) ? $param['gravity_timestamp'] : 0;

        $cSign = YinliTokenService::getInstance()->createSign($timestamp);

        $result = [ 'err_msg' => 'sign err' ];
        if($sign === $cSign )
        {
            // ["access_token": "********", "expires_in": 7200];
            list($token,$ttl) = WeixinService::getInstance()->getYinliFmtData();
            $result = [
                'access_token' => $token,
                'expires_in'   => $ttl,
            ];
        }
        var_dump($result);

        return $this->rJson( $result );
    }

    protected function rJson(array $result):bool
    {

        if (!$this->response()->isEndResponse()) 
        {
            $this->response()->write(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
            $this->response()->withStatus(200);

            return true ;
        } else {
            return false;
        }
    }
}