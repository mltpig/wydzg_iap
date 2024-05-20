<?php
namespace App\Api\Validate;
use EasySwoole\Component\CoroutineSingleTon;

class ClassPath
{
    use CoroutineSingleTon;

    private $classList = array(
        "wydzg_iaa_login"   => "\\App\\Api\\Validate\\Server\\Iaa",
        "wydzg_iap_login"   => "\\App\\Api\\Validate\\Server\\Login",
    );

    public function getPath(string $event):string
    {
        return array_key_exists($event,$this->classList)? $this->classList[$event] :'';
    }
}
