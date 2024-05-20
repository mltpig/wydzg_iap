<?php
namespace App\Api\Validate\Spirit;
use EasySwoole\Component\CoroutineSingleTon;

class Cut
{
    use CoroutineSingleTon;

    private $rules = [
        'method'    => 'required|notEmpty',
        'timestamp' => 'required|notEmpty',
        'sign'      => 'required|notEmpty',
        'squad'     => 'required|notEmpty',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
