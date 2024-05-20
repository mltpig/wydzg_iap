<?php
namespace App\Api\Validate\SecretTower;
use EasySwoole\Component\CoroutineSingleTon;

class Rank
{
    use CoroutineSingleTon;

    private $rules = [
        'method'    => 'required|notEmpty',
        'timestamp' => 'required|notEmpty',
        'sign'      => 'required|notEmpty',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
