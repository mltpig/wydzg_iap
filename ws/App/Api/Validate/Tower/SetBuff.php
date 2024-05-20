<?php
namespace App\Api\Validate\Tower;
use EasySwoole\Component\CoroutineSingleTon;

class SetBuff
{
    use CoroutineSingleTon;

    private $rules = [
        'method'    => 'required|notEmpty',
        'timestamp' => 'required|notEmpty',
        'sign'      => 'required|notEmpty',
        'long'      => 'required',
        'temp'      => 'required',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
