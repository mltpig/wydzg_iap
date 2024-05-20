<?php
namespace App\Api\Controller;
use EasySwoole\EasySwoole\ServerManager;

class Close extends Base
{
    public function index()
    {	
       ServerManager::getInstance()->getSwooleServer()->close($this->caller()->getClient()->getFd());
    }
}