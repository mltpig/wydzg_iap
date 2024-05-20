<?php
namespace App\Api\Validate\Pay\Wx;
use EasySwoole\Component\CoroutineSingleTon;

class Order
{
    use CoroutineSingleTon;

    private $rules = [
        'method'       => 'required|notEmpty',
        'timestamp'    => 'required|notEmpty',
        'sign'         => 'required|notEmpty',
        'rechargeId'   => 'required|notEmpty',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
