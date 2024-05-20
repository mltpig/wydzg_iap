<?php
namespace App\Api\Controller\Ext;
use App\Api\Controller\BaseController;

class Set  extends BaseController
{

    public function index()
    {
        $this->player->setData('ext',null,$this->param['ext']);

        $this->sendMsg([]);
    }

}