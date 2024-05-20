<?php
namespace App\Api\Validate\Chara;
use EasySwoole\Component\CoroutineSingleTon;

class Upgrade
{
    use CoroutineSingleTon;

    private $rules = [
        'method'       => 'required|notEmpty',
        'timestamp'    => 'required|notEmpty',
        'sign'         => 'required|notEmpty',
        'type'         => 'required|notEmpty|between:2,2',
        'id'           => 'required|notEmpty',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
