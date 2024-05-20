<?php
namespace App\Api\Validate\GiftCode;
use EasySwoole\Component\CoroutineSingleTon;

class Receive
{
    use CoroutineSingleTon;

    private $rules = [
        'method'     => 'required|notEmpty',
        'timestamp'  => 'required|notEmpty',
        'sign'       => 'required|notEmpty',
        'giftCode'   => 'required|notEmpty',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
