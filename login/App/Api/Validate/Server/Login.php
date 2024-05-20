<?php
namespace App\Api\Validate\Server;
use EasySwoole\Component\CoroutineSingleTon;

class Login
{
    use CoroutineSingleTon;

    private $rules = [
        'code' => 'required',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
