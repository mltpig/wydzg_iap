<?php
namespace App\Api\Validate\Equipment;
use EasySwoole\Component\CoroutineSingleTon;

class UpStage
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
