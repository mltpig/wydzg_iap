<?php
namespace App\Api\Validate\Dongfu\Comrade;
use EasySwoole\Component\CoroutineSingleTon;

class Battle
{
    use CoroutineSingleTon;

    private $rules = [
        'method'     => 'required|notEmpty',
        'timestamp'  => 'required|notEmpty',
        'sign'       => 'required|notEmpty',
        'id'         => 'required|notEmpty|integer',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
