<?php
namespace App\Api\Validate\Pet;
use EasySwoole\Component\CoroutineSingleTon;

class Buy
{
    use CoroutineSingleTon;

    private $rules = [
        'method'    => 'required|notEmpty',
        'timestamp' => 'required|notEmpty',
        'sign'      => 'required|notEmpty',
        'id'        => 'required|notEmpty|between:0,2',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
