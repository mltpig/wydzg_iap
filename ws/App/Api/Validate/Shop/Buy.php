<?php
namespace App\Api\Validate\Shop;
use EasySwoole\Component\CoroutineSingleTon;

class Buy
{
    use CoroutineSingleTon;

    private $rules = [
        'method'     => 'required|notEmpty',
        'timestamp'  => 'required|notEmpty',
        'sign'       => 'required|notEmpty',
        'id'         => 'required|notEmpty|integer',
        'num'        => 'required|notEmpty|integer|min:1',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
