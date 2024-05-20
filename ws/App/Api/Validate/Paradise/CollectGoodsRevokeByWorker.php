<?php
namespace App\Api\Validate\Paradise;
use EasySwoole\Component\CoroutineSingleTon;

class CollectGoodsRevokeByWorker
{
    use CoroutineSingleTon;

    private $rules = [
        'method'     => 'required|notEmpty',
        'timestamp'  => 'required|notEmpty',
        'sign'       => 'required|notEmpty',
        'id'         => 'required|notEmpty|between:1,10',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
