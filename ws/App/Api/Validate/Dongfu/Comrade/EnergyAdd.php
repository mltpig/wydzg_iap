<?php
namespace App\Api\Validate\Dongfu\Comrade;
use EasySwoole\Component\CoroutineSingleTon;

class EnergyAdd
{
    use CoroutineSingleTon;

    private $rules = [
        'method'     => 'required|notEmpty',
        'timestamp'  => 'required|notEmpty',
        'sign'       => 'required|notEmpty',
        'isAd'       => 'required|notEmpty|integer|between:0,1',
        'number'     => 'required|notEmpty|integer',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
