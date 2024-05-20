<?php
namespace App\Api\Validate\Bag;
use EasySwoole\Component\CoroutineSingleTon;

class OpenBox
{
    use CoroutineSingleTon;

    private $rules = [
        'method'       => 'required|notEmpty',
        'timestamp'    => 'required|notEmpty',
        'sign'         => 'required|notEmpty',
        'gid'          => 'required|notEmpty|integer',
        'num'          => 'required|notEmpty|integer|min:1',
        'target'       => 'required',
    ];
      
    public function getRules():array
    {
        return $this->rules;
    }
}
