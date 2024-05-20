<?php
namespace App\Api\Validate\Paradise\Around;
use EasySwoole\Component\CoroutineSingleTon;

class Refresh
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
