<?php
namespace App\Api\Controller\Ext;
use App\Api\Service\TaskService;
use App\Api\Controller\BaseController;

class Video  extends BaseController
{

    public function index()
    {

        TaskService::getInstance()->setVal($this->player,1003,1,'add');

        $this->sendMsg([]);
    }

}