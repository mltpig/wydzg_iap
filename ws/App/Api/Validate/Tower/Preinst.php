<?php
namespace App\Api\Validate\Tower;
use EasySwoole\Component\CoroutineSingleTon;

class Preinst
{
    use CoroutineSingleTon;

    private $rules = [
        'method'    => 'required|notEmpty',
        'timestamp' => 'required|notEmpty',
        'sign'      => 'required|notEmpty',
        'preinst'   => 'required',
        'open'      => 'required',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
