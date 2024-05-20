<?php
namespace App\Api\Validate\Cloud;
use EasySwoole\Component\CoroutineSingleTon;

class Upgrade
{
    use CoroutineSingleTon;

    private $rules = [
        'method'       => 'required|notEmpty',
        'timestamp'    => 'required|notEmpty',
        'sign'         => 'required|notEmpty',
        'id'           => 'required|notEmpty',
        'quick'        => 'required|notEmpty|between:0,1',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
