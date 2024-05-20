<?php
namespace App\Api\Validate\Dongfu\Comrade;
use EasySwoole\Component\CoroutineSingleTon;

class Visit
{
    use CoroutineSingleTon;

    private $rules = [
        'method'     => 'required|notEmpty',
        'timestamp'  => 'required|notEmpty',
        'sign'       => 'required|notEmpty',
        'quick'      => 'required|notEmpty|integer|between:0,1',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
