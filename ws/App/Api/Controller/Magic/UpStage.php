<?php
namespace App\Api\Controller\Magic;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigMagic;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\MagicService;
use App\Api\Service\TaskService;

class UpStage extends BaseController
{

    public function index()
    {
        $param   = $this->param;
        $magic   = $this->player->getData('magic');
        $upgrade = ConfigParam::getInstance()->getFmtParam('MAGIC_UPGRADE_PARAM');

        $result = '未衍化';
        if(array_key_exists($param['id'],$magic['bag']))
        {
            $config   = ConfigMagic::getInstance()->getOne($param['id']);

            $old      = $magic['bag'][$param['id']];
            $maxStage = count($upgrade) + 1;
            $result = '已达最高等阶';
            if($old['stage'] < $maxStage )
            {
                $stage  = $old['stage'];
                $result = '道具不足';
                if($this->player->getGoods($config['item_id']) >= $upgrade[$stage - 1])
                {
                    $remain = [];
                    $old['stage']++;
                    $this->player->setMagic('bag',$param['id'],$old,'multiSet');

                    $goodsList[] = ['type' => GOODS_TYPE_1, 'gid' => $config['item_id'], 'num' => -$upgrade[$stage - 1]];
                    $this->player->goodsBridge($goodsList,'神通进阶');

                    $remain[] = ['type' => GOODS_TYPE_1, 'gid' => $config['item_id'], 'num' => $this->player->getGoods($config['item_id'])];

                    $result = [
                        'magic'     => MagicService::getInstance()->getMagicFmtData($this->player),
                        'remain'    => $remain,
                    ];
                }
            }
        }

        $this->sendMsg( $result );
    }

}