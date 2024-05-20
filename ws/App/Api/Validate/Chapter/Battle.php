<?php
namespace App\Api\Validate\Chapter;
use EasySwoole\Component\CoroutineSingleTon;

class Battle
{
    use CoroutineSingleTon;

    private $rules = [
        'method'       => 'required|notEmpty',
        'timestamp'    => 'required|notEmpty',
        'sign'         => 'required|notEmpty',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
