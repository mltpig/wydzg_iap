<?php
namespace App\Api\Validate;
use EasySwoole\Validate\Validate;
use EasySwoole\Component\CoroutineSingleTon;

class ParamCheck
{
    use CoroutineSingleTon;

    public function Validate(string $raw):array
    {
        $param = json_decode($raw, true);
        if(!is_array($param))  return ['code'=> 1 ,'msg'=>'数据格式不对'];
        if(!array_key_exists('method',$param) || !$param['method']) return ['code'=> 1,'msg'=>'method 不可为空'];

        $method = $param['method'];

        $class = ValidateClass::getInstance()->getPath($method);

        if(!$class) return ['code' => 1 , 'msg' => $method." 验证规则设置错误"];
        if(!class_exists($class)) return ['code' => 1 , 'msg' => $method." 验证类未添加"];

        $rules = $class::getInstance()->getRules();
        if($rules === '') return ['code' => 1 , 'msg' => $method." 验证规则未设置"];

        $validate = Validate::make($rules);
        if(!$validate->validate($param)) return ['code' => 1 , 'msg' => $validate->getError()->__toString() ] ;
        
        $arg = $validate->getVerifiedData();
        
        //if( ($data['timestamp'] - time()) > 30 )  return ['code' => 2 , 'msg' => '请更新时间戳' ] ;

        //if($this->createSign($arg) !== $param['sign'] ) return  ['code' => 3 , 'msg' => "sign 验证错误"];
        $controllerClass  = str_replace('Validate','Controller',$class);

        if(!class_exists($controllerClass)) return ['code' => 1 , 'msg' => $method." 方法未添加"];

        return  [ 'code' => 0 ,'data' => $arg ,'class' => $controllerClass ];
    }

    public function createSign(array $param):string
    {
        ksort($param);
        $str = '';
        foreach ($param as $key => $val) 
        {
            if($key === 'sign' || is_array($val)) continue;
            $str .= $key.'='.$val.'&';
        }

        $secret = $this->getSecretkey($param['timestamp']);

        return strtolower(md5($str.$secret)) ;
    }

    public function getSecretkey(string $timestamp):string
    {
        $string = md5( substr($timestamp,-4) );
        $rand   = [20,7,19,28,10,12,25,2,20,19,10,10,21,6,1,14,31,8,14,18,1,24,12,20,16,20,10,20,16,24,5,30];
        $secret = '';
        foreach ( $rand as $index) 
        {
            $secret .= $string[$index];
        }
        return $secret;
    }

}
