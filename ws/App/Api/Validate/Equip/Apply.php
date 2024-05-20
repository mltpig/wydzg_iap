<?php
namespace App\Api\Validate\Equip;
use EasySwoole\Component\CoroutineSingleTon;

class Apply
{
    use CoroutineSingleTon;

    private $rules = [
        'method'       => 'required|notEmpty',
        'timestamp'    => 'required|notEmpty',
        'sign'         => 'required|notEmpty',
        'auto'         => 'required|notEmpty|between:0,1',
        'index'        => 'required|notEmpty',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
