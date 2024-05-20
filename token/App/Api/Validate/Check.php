<?php
namespace App\Api\Validate;
use EasySwoole\Validate\Validate;
use EasySwoole\Component\CoroutineSingleTon;

class Check
{
    use CoroutineSingleTon;

    public $secret = 'vfxloCPx2oGssv7qqXekl1D7U3cKj2TN';

    public function getValidateData(string $method,array $param):array
    {
        
        $class = ClassPath::getInstance()->getPath($method);

        if(!$class) return ['err_code' => ERROR , 'msg' => "参数错误"];
        if(!class_exists($class)) return ['err_code' => ERROR , 'msg' => " 参数错误1"];

        $rules = $class::getInstance()->getRules();

        if(!$rules) return ['err_code' => ERROR , 'msg' => " 参数错误2"];

        $validate = Validate::make($rules);

        if(!$validate->validate($param)) return ['err_code' => ERROR , 'msg' => $validate->getError()->__toString() ] ;

        if( time() - $param['timestamp']  > 5 )  return ['err_code' => ERROR , 'msg' => 'timestamp 错误' ] ;
        
        if($this->createSign($param,$this->secret) !== $param['sign'] ) return  [ 'err_code' => ERROR , 'msg' =>'参数错误3' ];

        $data = $validate->getVerifiedData();
        
        return  $data;
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
