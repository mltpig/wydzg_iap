<?php
namespace App\Api\Validate\Spirit;
use EasySwoole\Component\CoroutineSingleTon;

class Draw
{
    use CoroutineSingleTon;

    private $rules = [
        'method'    => 'required|notEmpty',
        'timestamp' => 'required|notEmpty',
        'sign'      => 'required|notEmpty',
        'num'       => 'required|notEmpty|between:1,5',
        'isAd'      => 'required|notEmpty|between:0,1',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
