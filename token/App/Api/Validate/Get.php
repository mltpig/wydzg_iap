<?php
namespace App\Api\Validate;
use EasySwoole\Component\CoroutineSingleTon;

class Get
{
    use CoroutineSingleTon;

    private $rules = [
        'tag'       => 'required',
        'code'      => 'required',
        'timestamp' => 'required|timestamp',
        'sign'      => 'required',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
