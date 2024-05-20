<?php
namespace App\Api\Validate\Player;
use EasySwoole\Component\CoroutineSingleTon;

class InitBelong
{
    use CoroutineSingleTon;

    private $rules = [
        'method'       => 'required|notEmpty',
        'timestamp'    => 'required|notEmpty',
        'sign'         => 'required|notEmpty',
        'belong'       => 'required|notEmpty|between:1,3',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
