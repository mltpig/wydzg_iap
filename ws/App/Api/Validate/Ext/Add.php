<?php
namespace App\Api\Validate\Ext;
use EasySwoole\Component\CoroutineSingleTon;

class Add
{
    use CoroutineSingleTon;

    private $rules = [
        'method'     => 'required|notEmpty',
        'timestamp'  => 'required|notEmpty',
        'sign'       => 'required|notEmpty',
        'gid'        => 'required|notEmpty',
        'num'        => 'required|notEmpty|between:1,999999999',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
