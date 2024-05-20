<?php
namespace App\Api\Controller\Ext;
use App\Api\Service\Module\TicketService;
use EasySwoole\EasySwoole\Core;
use App\Api\Controller\BaseController;

class MdsAdd  extends BaseController
{

    public function index()
    {
        $result = '无效的物品ID';

        if(Core::getInstance()->runMode() === 'dev')
        {
            
            $rewards = [
                ['gid' => $this->param['gid'],'ticket' => $this->param['num'],'type' => 1 ]
            ];

            $site   = $this->player->getData('site');
            $openid = $this->player->getData('openid');

            try {
                // $result = VoucherService::getInstance($openid,$site)->getBalance();
                // var_dump($result);
                $result = TicketService::getInstance($this->player)->present($this->param['num']);
                // var_dump($result);
                // $result = VoucherService::getInstance()->pay(20);
                // var_dump($result);
                $result = [ 'ticket' => $result];

            } catch (\Throwable $th) {
               $result = $th->getMessage();
            }
            
        }

        $this->sendMsg($result);
    }

}