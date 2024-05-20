<?php
namespace App\Api\Controller\Fund;
use App\Api\Table\ConfigParam;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\FundService;

class Get extends BaseController
{

    public function index()
    {
        $param = $this->param;
        $group = $param['group'];
        $tag         = FundService::getInstance()->getGroupWhereArg($group);
        
        $result = [
            'fund' => FundService::getInstance()->getFundGroupData($this->player,$group),
            'config'      => [
                'state' => $this->player->getArg($tag),
            ],
        ];

        $this->sendMsg( $result );
    }

}