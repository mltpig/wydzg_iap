<?php
namespace App\Api\Validate\Doufa;
use EasySwoole\Component\CoroutineSingleTon;

class RecordBattle
{
    use CoroutineSingleTon;

    private $rules = [
        'method'     => 'required|notEmpty',
        'timestamp'  => 'required|notEmpty',
        'sign'       => 'required|notEmpty',
        'rid'        => 'required|notEmpty',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
