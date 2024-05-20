<?php
namespace App\Api\Validate\Chara;
use EasySwoole\Component\CoroutineSingleTon;

class Modify
{
    use CoroutineSingleTon;

    private $rules = [
        'method'       => 'required|notEmpty',
        'timestamp'    => 'required|notEmpty',
        'sign'         => 'required|notEmpty',
        'type'         => 'required|notEmpty|between:1,2',
        'value'        => 'required|notEmpty',
        'belong'       => 'required|notEmpty|between:0,3',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
