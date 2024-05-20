<?php
namespace App\Api\Validate\Cloud;
use EasySwoole\Component\CoroutineSingleTon;

class Unlock
{
    use CoroutineSingleTon;

    private $rules = [
        'method'       => 'required|notEmpty',
        'timestamp'    => 'required|notEmpty',
        'sign'         => 'required|notEmpty',
        'id'           => 'required|notEmpty',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
