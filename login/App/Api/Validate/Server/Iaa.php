<?php
namespace App\Api\Validate\Server;
use EasySwoole\Component\CoroutineSingleTon;

class Iaa
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
