<?php
namespace App\Api\Validate\Paradise;
use EasySwoole\Component\CoroutineSingleTon;

class RefreshGoods
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
