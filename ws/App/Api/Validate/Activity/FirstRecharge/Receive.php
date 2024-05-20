<?php
namespace App\Api\Validate\Activity\FirstRecharge;
use EasySwoole\Component\CoroutineSingleTon;

class Receive
{
    use CoroutineSingleTon;

    private $rules = [
        'method'     => 'required|notEmpty',
        'timestamp'  => 'required|notEmpty',
        'sign'       => 'required|notEmpty',
        'id'         => 'required|notEmpty|integer|between:1,3',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
