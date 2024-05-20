<?php
namespace App\Api\Validate\LifetimeCard;
use EasySwoole\Component\CoroutineSingleTon;

class Buy
{
    use CoroutineSingleTon;

    private $rules = [
        'method'     => 'required|notEmpty',
        'timestamp'  => 'required|notEmpty',
        'sign'       => 'required|notEmpty',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}