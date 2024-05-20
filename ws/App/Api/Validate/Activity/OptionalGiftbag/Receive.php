<?php
namespace App\Api\Validate\Activity\OptionalGiftbag;
use EasySwoole\Component\CoroutineSingleTon;

class Receive
{
    use CoroutineSingleTon;

    private $rules = [
        'method'     => 'required|notEmpty',
        'timestamp'  => 'required|notEmpty',
        'sign'       => 'required|notEmpty',
        'groupid'    => 'required|notEmpty|integer',
        'option'     => 'required',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
