<?php
namespace App\Api\Validate\Doufa;
use EasySwoole\Component\CoroutineSingleTon;

class Enemys
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
