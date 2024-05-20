<?php
namespace App\Api\Controller\Doufa;
use App\Api\Service\DoufaService;

use App\Api\Controller\BaseController;


//装备上阵
class Record extends BaseController
{

    public function index()
    {

        $record = DoufaService::getInstance()->getRecord($this->param['uid'],$this->param['site']);

        $this->sendMsg( [ 'list' => $record ] );
    }

}