<?php
namespace App\Api\Validate;
use EasySwoole\Validate\Validate;
use EasySwoole\Component\CoroutineSingleTon;

class CheckIn
{
    use CoroutineSingleTon;
    public $secret = 'vfxloCPx2oGssv7qqXekl1D7U3cKj2TN';

    public function getValidateData(string $event,array $param):array
    {
        
        $class = ClassPath::getInstance()->getPath($event);

        if(!$class) return ['err_code' => 1 , 'msg' => $event." 参数错误"];
        if(!class_exists($class)) return ['err_code' => 1 , 'msg' => $event." 参数错误1"];

        $rules = $class::getInstance()->getRules();

        if(!$rules) return ['err_code' => 1 , 'msg' => $event." 参数错误2"];

        $validate = Validate::make($rules);
        if(!$validate->validate($param)) return ['err_code' => 2 , 'msg' => $validate->getError()->__toString() ] ;
        
        $data = $validate->getVerifiedData();
        
        return  $data;
    }

}
