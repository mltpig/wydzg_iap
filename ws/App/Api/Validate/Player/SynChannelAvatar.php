<?php
namespace App\Api\Validate\Player;
use EasySwoole\Component\CoroutineSingleTon;

class SynChannelAvatar
{
    use CoroutineSingleTon;

    private $rules = [
        'method'       => 'required|notEmpty',
        'timestamp'    => 'required|notEmpty',
        'sign'         => 'required|notEmpty',
        'avatar'       => 'required|notEmpty|lengthMax:300',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
