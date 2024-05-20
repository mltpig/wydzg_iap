<?php
namespace App\Api\Validate\Email;
use EasySwoole\Component\CoroutineSingleTon;

class Receive
{
    use CoroutineSingleTon;

    private $rules = [
        'method'     => 'required|notEmpty',
        'timestamp'  => 'required|notEmpty',
        'sign'       => 'required|notEmpty',
        'type'       => 'required|notEmpty|between:1,2',
        'id'         => 'required|notEmpty',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
