<?php
namespace App\Api\Validate\Activity\ShangGu;
use EasySwoole\Component\CoroutineSingleTon;

class Get
{
    use CoroutineSingleTon;

    private $rules = [
        'method'        => 'required|notEmpty',
        'timestamp'     => 'required|notEmpty',
        'sign'          => 'required|notEmpty',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
