<?php
namespace App\Api\Validate\Tower;
use EasySwoole\Component\CoroutineSingleTon;

class DelBuff
{
    use CoroutineSingleTon;

    private $rules = [
        'method'    => 'required|notEmpty',
        'timestamp' => 'required|notEmpty',
        'sign'      => 'required|notEmpty',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
