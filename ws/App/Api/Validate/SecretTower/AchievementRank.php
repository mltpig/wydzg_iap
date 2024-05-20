<?php
namespace App\Api\Validate\SecretTower;
use EasySwoole\Component\CoroutineSingleTon;

class AchievementRank
{
    use CoroutineSingleTon;

    private $rules = [
        'method'    => 'required|notEmpty',
        'timestamp' => 'required|notEmpty',
        'sign'      => 'required|notEmpty',
        'floor'     => 'required|notEmpty',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
