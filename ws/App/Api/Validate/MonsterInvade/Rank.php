<?php
namespace App\Api\Validate\MonsterInvade;
use EasySwoole\Component\CoroutineSingleTon;

class Rank
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
