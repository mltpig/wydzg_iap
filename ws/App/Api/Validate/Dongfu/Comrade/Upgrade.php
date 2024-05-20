<?php
namespace App\Api\Validate\Dongfu\Comrade;
use EasySwoole\Component\CoroutineSingleTon;

class Upgrade
{
    use CoroutineSingleTon;

    private $rules = [
        'method'     => 'required|notEmpty',
        'timestamp'  => 'required|notEmpty',
        'sign'       => 'required|notEmpty',
        'id'         => 'required|notEmpty|integer',
        'gid'        => 'required|notEmpty|integer',
        'quick'      => 'required|notEmpty|integer|between:0,1',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
