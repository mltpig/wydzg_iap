<?php
namespace App\Api\Validate\Player;
use EasySwoole\Component\CoroutineSingleTon;

class ModifyNickname
{
    use CoroutineSingleTon;

    private $rules = [
        'method'       => 'required|notEmpty',
        'timestamp'    => 'required|notEmpty',
        'sign'         => 'required|notEmpty',
        'newName'      => 'required|notEmpty|MbLengthMax:15',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
