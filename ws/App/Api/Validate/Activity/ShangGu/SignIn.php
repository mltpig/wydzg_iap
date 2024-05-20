<?php
namespace App\Api\Validate\Activity\ShangGu;
use EasySwoole\Component\CoroutineSingleTon;

class SignIn
{
    use CoroutineSingleTon;

    private $rules = [
        'method'     => 'required|notEmpty',
        'timestamp'  => 'required|notEmpty',
        'sign'       => 'required|notEmpty',
        'day'        => 'required|notEmpty',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
