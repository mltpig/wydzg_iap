<?php
namespace App\Api\Controller;
use FastRoute\RouteCollector;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EasySwoole\Http\AbstractInterface\AbstractRouter;

class Router extends AbstractRouter
{
    function initialize(RouteCollector $routeCollector)
    {
        $this->setMethodNotAllowCallBack(function (Request $request,Response $response){
            $response->write('403');
            return false;
        });
        $this->setRouterNotFoundCallBack(function (Request $request,Response $response){
            $response->write('404');
            return false;
        });

        //获取邮件物品列表
        $routeCollector->get('/wydzg_iaa_login','/Server/Iaa');
        $routeCollector->get('/wydzg_iap_login','/Server/Login');
    }


}