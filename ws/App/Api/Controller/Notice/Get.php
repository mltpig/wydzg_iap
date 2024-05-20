<?php
namespace App\Api\Controller\Notice;
use App\Api\Service\NoticeService;
use App\Api\Controller\BaseController;

//抽卡
class Get extends BaseController
{

    public function index()
    {

        $this->sendMsg( ['notice' => NoticeService::getInstance()->getList() ] );
    }

}