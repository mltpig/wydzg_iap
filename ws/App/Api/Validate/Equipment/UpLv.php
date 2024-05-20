<?php
namespace App\Api\Validate\Equipment;
use EasySwoole\Component\CoroutineSingleTon;

class UpLv
{
    use CoroutineSingleTon;

    private $rules = [
        'method'    => 'required|notEmpty',
        'timestamp' => 'required|notEmpty',
        'sign'      => 'required|notEmpty',
        'open'      => 'required',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
