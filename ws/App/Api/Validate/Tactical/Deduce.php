<?php
namespace App\Api\Validate\Tactical;
use EasySwoole\Component\CoroutineSingleTon;

class Deduce
{
    use CoroutineSingleTon;

    private $rules = [
        'method'       => 'required|notEmpty',
        'timestamp'    => 'required|notEmpty',
        'sign'         => 'required|notEmpty',
        'auto'         => 'required|notEmpty|between:0,1',//是否自动
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
