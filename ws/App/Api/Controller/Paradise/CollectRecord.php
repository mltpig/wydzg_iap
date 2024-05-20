<?php
namespace App\Api\Controller\Paradise;
use App\Api\Service\ParadisService;
use App\Api\Controller\BaseController;


//装备上阵
class CollectRecord extends BaseController
{

    public function index()
    {

        $record = ParadisService::getInstance()->getRecord($this->param['uid'],$this->param['site']);
        
        $this->sendMsg( [ 'list' => $record ] );
    }

}