<?php
namespace App\Api\Validate\Paradise;
use EasySwoole\Component\CoroutineSingleTon;

class CollectGoods
{
    use CoroutineSingleTon;

    private $rules = [
        'method'     => 'required|notEmpty',
        'timestamp'  => 'required|notEmpty',
        'sign'       => 'required|notEmpty',
        'id'         => 'required|notEmpty|between:1,6',
        'num'        => 'required|notEmpty|between:1,4',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
