<?php
namespace App\Api\Validate\Pay\Wx;
use EasySwoole\Component\CoroutineSingleTon;

class Query
{
    use CoroutineSingleTon;

    private $rules = [
        'method'       => 'required|notEmpty',
        'timestamp'    => 'required|notEmpty',
        'sign'         => 'required|notEmpty',
        'outTradeNo'   => 'required|notEmpty',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
