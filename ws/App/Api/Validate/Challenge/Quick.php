<?php
namespace App\Api\Validate\Challenge;
use EasySwoole\Component\CoroutineSingleTon;

class Quick
{
    use CoroutineSingleTon;

    private $rules = [
        'method'     => 'required|notEmpty',
        'timestamp'  => 'required|notEmpty',
        'sign'       => 'required|notEmpty',
        'isAd'       => 'required|notEmpty|between:0,1',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
