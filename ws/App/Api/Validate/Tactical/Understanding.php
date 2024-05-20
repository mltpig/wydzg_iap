<?php
namespace App\Api\Validate\Tactical;
use EasySwoole\Component\CoroutineSingleTon;

class Understanding
{
    use CoroutineSingleTon;

    private $rules = [
        'method'       => 'required|notEmpty',
        'timestamp'    => 'required|notEmpty',
        'sign'         => 'required|notEmpty',
        'num'        => 'required|notEmpty|integer|min:1',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
