<?php
namespace App\Api\Validate\Activity\Channel;
use EasySwoole\Component\CoroutineSingleTon;

class ReceiveDesktop
{
    use CoroutineSingleTon;

    private $rules = [
        'method'     => 'required|notEmpty',
        'timestamp'  => 'required|notEmpty',
        'sign'       => 'required|notEmpty',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
