<?php
namespace App\Api\Controller\Server;
use App\Api\Service\ServerService;
use App\Api\Service\NodeService;
use App\Api\Service\NoticeService;
use App\Api\Service\Channel\WeixinService;
use App\Api\Controller\BaseController;

class Login extends BaseController
{

    public function index()
    {

        $result = WeixinService::getInstance()->getUserInfo($this->param['code']);

        if(is_array($result))
        {
            NodeService::getInstance()->addMember($result['openid'],$result['session_key']);

            $result = [
                "serverList"        => ServerService::getInstance()->getList(),
                "playerServerList"  => NodeService::getInstance()->getPlayerList($result['openid']),
                "notice"            => NoticeService::getInstance()->getNotice(),
                "recommendServerId" => 2,
                "serverTime"        => time(),
                "userInfo"          => [
                    'code'          => $result['openid']
                ],
            ];
        }
        

        return $this->rJson( $result );
    }
}