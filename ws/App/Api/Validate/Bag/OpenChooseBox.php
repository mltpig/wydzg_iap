<?php
namespace App\Api\Validate\Bag;
use EasySwoole\Component\CoroutineSingleTon;

class OpenChooseBox
{
    use CoroutineSingleTon;

    private $rules = [
        'method'       => 'required|notEmpty',
        'timestamp'    => 'required|notEmpty',
        'sign'         => 'required|notEmpty',
        'gid'          => 'required|notEmpty|integer',
        'target'       => 'required',
    ];
      
    public function getRules():array
    {
        return $this->rules;
    }
}
