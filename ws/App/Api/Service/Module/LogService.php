<?php
namespace App\Api\Service\Module;

use App\Task\LogTask;
use EasySwoole\EasySwoole\Task\TaskManager;
use EasySwoole\Component\CoroutineSingleTon;

class LogService
{
    use CoroutineSingleTon;

    private $list = [];

    public function push(array $log):void
    {
        $this->list[] = $log;
    }

    public function save():void
    {
        TaskManager::getInstance()->async(new LogTask( $this->list ));
        
        $this->list = [];
    }

}