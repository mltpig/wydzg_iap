<?php
namespace App\Api\Validate\Activity\Channel;
use EasySwoole\Component\CoroutineSingleTon;

class Get
{
    use CoroutineSingleTon;

    private $rules = [
        'method'        => 'required|notEmpty',
        'timestamp'     => 'required|notEmpty',
        'sign'          => 'required|notEmpty',
        'iv'            => 'required|notEmpty',
        'sessionKey'    => 'required|notEmpty',
        'encryptedData' => 'required|notEmpty',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
