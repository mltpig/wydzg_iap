<?php
namespace App\Api\Validate\YuanBao;
use EasySwoole\Component\CoroutineSingleTon;

class Buy
{
    use CoroutineSingleTon;

    private $rules = [
        'method'     => 'required|notEmpty',
        'timestamp'  => 'required|notEmpty',
        'sign'       => 'required|notEmpty',
        'id'         => 'required|notEmpty|integer',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
