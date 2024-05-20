<?php
namespace App\Api\Validate;
use EasySwoole\Component\CoroutineSingleTon;

class ClassPath
{
    use CoroutineSingleTon;

    private $maps = array(
        "get"   => "\\App\\Api\\Validate\\Get",
    );

    public function getPath(string $method):string
    {
        return array_key_exists($method,$this->maps)? $this->maps[$method] :'';
    }
}
