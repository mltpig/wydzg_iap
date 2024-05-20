<?php
namespace App\Api\Validate\Activity\OverFlowGift;
use EasySwoole\Component\CoroutineSingleTon;

class Buy
{
    use CoroutineSingleTon;

    private $rules = [
        'method'     => 'required|notEmpty',
        'timestamp'  => 'required|notEmpty',
        'sign'       => 'required|notEmpty',
        'id'         => 'required|notEmpty',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
