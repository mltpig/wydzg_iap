<?php
namespace App\Api\Validate\Ext;
use EasySwoole\Component\CoroutineSingleTon;

class MdsAdd
{
    use CoroutineSingleTon;

    private $rules = [
        'method'     => 'required|notEmpty',
        'timestamp'  => 'required|notEmpty',
        'sign'       => 'required|notEmpty',
        'num'        => 'required|notEmpty|between:1,10000',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
