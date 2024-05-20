<?php
namespace App\Api\Validate\Ext;
use EasySwoole\Component\CoroutineSingleTon;

class AddAttribute
{
    use CoroutineSingleTon;

    private $rules = [
        'method'     => 'required|notEmpty',
        'timestamp'  => 'required|notEmpty',
        'sign'       => 'required|notEmpty',
        'val'        => 'required|notEmpty|between:1,999999999',
        'type'       => 'required|notEmpty|between:1,6',
        'litye'       => 'required|notEmpty|between:1,20',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
