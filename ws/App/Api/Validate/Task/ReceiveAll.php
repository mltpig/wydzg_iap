<?php
namespace App\Api\Validate\Task;
use EasySwoole\Component\CoroutineSingleTon;

class ReceiveAll
{
    use CoroutineSingleTon;

    private $rules = [
        'method'     => 'required|notEmpty',
        'timestamp'  => 'required|notEmpty',
        'sign'       => 'required|notEmpty',
        'taskid'     => 'required|notEmpty',
        'scene'      => 'required|notEmpty',
    ];
    
    public function getRules():array
    {
        return $this->rules;
    }
}
