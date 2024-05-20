<?php
namespace app\controller;
use app\model\LogProp as Models;
use app\validate\LogProp as Valids;
use think\exception\ValidateException;


class Query extends BaseController
{
    public function index(Models $Models)
    {
        $param = $this->request->get() + $this->request->post();

        try {
            validate(Valids::class)->scene('get')->check($param);
            return fmtJosn(200,[
                "list"  => $Models->getPageData($param),
                "total" => $Models->getCount($param)
            ],"success");
        } catch (ValidateException $e) {

            return fmtJosn(400,[],$e->getError());
        }


    }

}
