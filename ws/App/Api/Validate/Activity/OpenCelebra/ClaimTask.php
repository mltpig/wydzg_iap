<?php
namespace App\Api\Validate\Activity\OpenCelebra;
use EasySwoole\Component\CoroutineSingleTon;

class ClaimTask
{
    use CoroutineSingleTon;

    private $rules = [
        'method'        => 'required|notEmpty',
        'timestamp'     => 'required|notEmpty',
        'sign'          => 'required|notEmpty',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
