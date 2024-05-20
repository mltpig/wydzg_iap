<?php
/**
 * 新增属性值修改
 */

namespace App\Api\Controller\Ext;

use App\Api\Controller\BaseController;
use App\Api\Utils\Consts;
use EasySwoole\EasySwoole\Core;

class AddAttribute extends BaseController
{
    public function index()
    {
        $result = '环境有误，非测试环境';
        if (Core::getInstance()->runMode() === 'dev') {
            $type = $this->param['type'];
            $secondType = $this->param['litye'];
            $val = $this->param['val'];
            $attr = $this->player->getTmp('attribute');

            switch ($type) {
                case 1://处理基础属性
                    $name = substr(Consts::BASIC_ATTRIBUTE[$secondType - 1], 5);
                    //prim_
                    break;
                case 3://处理第二词条属性
                    $name = Consts::SECOND_ATTRIBUTE[$secondType - 1];
                    break;
                case 4://处理第二词条抗性属性
                    $name = Consts::SECOND_DEF_ATTRIBUTE[$secondType - 1];
                    break;
                case 5://处理特殊属性
                    //为了防止出错，暂时屏蔽  8-13
                    $list = [8,9,10,11,12,13];
                    if(!in_array($secondType - 1,$list) ){
                        $name = Consts::SPECIAL_ATTRIBUTE[$secondType - 1];
                    }else{
                        $this->sendMsg('litye错误，暂时不支持这个类型'.$secondType);
                        return;
                    }
                    break;
                default:
                    $this->sendMsg('type错误，不属于1，3，4');
                    return;
            }
            $attr[$name] =  $val;
            $result = ['attribute' => $attr];
            $this->player->setTmp('attribute',$attr);

        }
        $this->sendMsg($result);
    }

}