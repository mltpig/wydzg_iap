<?php
namespace App\Api\Validate\Magic;
use EasySwoole\Component\CoroutineSingleTon;

class IhUpLv
{
    use CoroutineSingleTon;

    private $rules = [
        'method'    => 'required|notEmpty',
        'timestamp' => 'required|notEmpty',
        'sign'      => 'required|notEmpty',
        'id'        => 'required|notEmpty',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
