<?php
namespace App\Api\Validate\Equip;
use EasySwoole\Component\CoroutineSingleTon;

class Get
{
    use CoroutineSingleTon;

    private $rules = [
        'method'       => 'required|notEmpty',
        'timestamp'    => 'required|notEmpty',
        'sign'         => 'required|notEmpty',
        'multiple'     => 'required|notEmpty|between:1,6',
        'option1'      => 'required|notEmpty|between:0,6',
        'option2'      => 'required|notEmpty|between:0,6',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
