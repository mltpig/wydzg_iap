<?php
namespace App\Api\Validate\Pet;
use EasySwoole\Component\CoroutineSingleTon;

class Release
{
    use CoroutineSingleTon;

    private $rules = [
        'method'    => 'required|notEmpty',
        'timestamp' => 'required|notEmpty',
        'sign'      => 'required|notEmpty',
        'id'        => 'required|notEmpty|between:0,41',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
