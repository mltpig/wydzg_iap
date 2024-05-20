<?php
namespace App\Api\Validate\Paradise\Around;
use EasySwoole\Component\CoroutineSingleTon;

class CollectGoodsRevokeById
{
    use CoroutineSingleTon;

    private $rules = [
        'method'     => 'required|notEmpty',
        'timestamp'  => 'required|notEmpty',
        'sign'       => 'required|notEmpty',
        'id'         => 'required|notEmpty|between:1,10',
        'rid'        => 'required|notEmpty',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
