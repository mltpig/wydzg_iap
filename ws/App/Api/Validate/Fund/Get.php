<?php
namespace App\Api\Validate\Fund;
use EasySwoole\Component\CoroutineSingleTon;

class Get
{
    use CoroutineSingleTon;

    private $rules = [
        'method'    => 'required|notEmpty',
        'timestamp' => 'required|notEmpty',
        'sign'      => 'required|notEmpty',
        'group'     => 'required|notEmpty',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
