<?php
namespace App\Api\Controller\Bag;
use App\Api\Controller\BaseController;
use App\Api\Table\ConfigGoods;
use App\Api\Table\ConfigPets;
use App\Api\Table\ConfigSpirits;
use App\Api\Table\ConfigGoodsBox;
use App\Api\Table\ConfigMagic;
use App\Api\Service\Module\PetService;
use App\Api\Service\Module\GoodsBoxService;
use App\Api\Service\TaskService;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\Log\LoggerInterface;

class OpenChooseBox  extends BaseController
{

    public function index()
    {
        // gid num target
        $param  = $this->param;

        $result = 'target 格式错误';
        if(is_array($param['target']))
        {
            $gidnum  = array_sum($param['target']);
            $result  = '数量不足';
            if($this->player->getGoods($param['gid']) >= $gidnum)
            {
                $config = ConfigGoods::getInstance()->getOne($param['gid']);
                $result = '该物品不可开启';
                if($config && $config['type'] == 7)
                {
                    list( $map , $pool )    = ConfigGoodsBox::getInstance()->getAll($param['gid']);
                    $boxConfig              = $map[ randTable($pool) ];
                    $goodsInfo              = ConfigGoods::getInstance()->getOne($boxConfig['item_id']);
                    $result                 = '功能未解锁';
                    if($goodsInfo['type'] == 3)
                    {
                        $bag    = $this->player->getData('pet','bag');
                        $total  = count($bag);
                        $free   =  $total - count(array_filter($bag));
                        $result = '副将栏已满';
                        if($free >= $gidnum)
                        {
                            $reward = [];
                            $bagid  = PetService::getInstance()->checkFreeBag( $this->player->getData('pet','bag'));
                            if($bagid != -1)
                            {
                                foreach($param['target'] as $id => $number)
                                {
                                    $goodsList[] = $reward[] = ['type' => GOODS_TYPE_3,'gid' => $id,'num' => $number];
                                }
                            }
                            $goodsList[] = [ 'type' => GOODS_TYPE_7,'gid' => $param['gid'],'num' => -$gidnum ];
                            
                            $this->player->goodsBridge($goodsList,'开启自选宝箱-副将',$this->player->getGoods($param['gid']) );
                            $result = [
                                'remian' => $this->player->getGoods($param['gid']),
                                'reward' => $reward,
                                'head'   => $this->player->getData('head'),
                            ];
                        }
                    }else if($goodsInfo['type'] == 5){

                        $reward = $goodsList = [];
                        foreach($param['target'] as $id => $number)
                        {
                            $goodsList[] = $reward[] = ['type' => GOODS_TYPE_5, 'gid' => $id, 'num' => ($boxConfig['max_num'] * $number)];
                        }

                        $goodsList[] = [ 'type' => GOODS_TYPE_7,'gid' => $param['gid'],'num' => -$gidnum ] ;
                        $this->player->goodsBridge($goodsList,'开启自选宝箱-红颜',$this->player->getGoods($param['gid']) );

                        $result = [
                            'remian' => $this->player->getGoods($param['gid']),
                            'reward' => $reward,
                        ];
                    }else if($goodsInfo['type'] == 23){

                        $reward = $goodsList = [];
                        foreach($param['target'] as $id => $number)
                        {
                            $goodsList[] = $reward[] = ['type' => GOODS_TYPE_23, 'gid' => $id, 'num' => ($boxConfig['max_num'] * $number)];
                        }

                        $goodsList[] = [ 'type' => GOODS_TYPE_7,'gid' => $param['gid'],'num' => -$gidnum ] ;
                        $this->player->goodsBridge($goodsList,'开启自选宝箱-刻印',$this->player->getGoods($param['gid']) );

                        $result = [
                            'remian' => $this->player->getGoods($param['gid']),
                            'reward' => $reward,
                        ];
                    }else if($goodsInfo['type'] == 8){

                        $reward = $goodsList = [];
                        $item   = ConfigMagic::getInstance()->getItemId();
                        foreach($param['target'] as $item_id => $item_number)
                        {
                            $id           = $item[$item_id]['id'];
                            $bag          = $this->player->getData('magic','bag');
                            if(!array_key_exists($id,$bag))
                            {
                                $itemStone = [];
                                for($i = 1; $i <= $item[$item_id]['stone_num']; $i++)//每个品质固定stone_num
                                {
                                    $itemStone[$i] = 0;
                                }
                                $old = ['id' => $id, 'lv' => 1, 'stage' => 1, 'stone' => $itemStone];
                                $this->player->setMagic('bag',$id,$old,'multiSet');
                                $reward[]    = ['type' => GOODS_TYPE_8, 'gid' => $item_id, 'num' => 1];
                                
                                if(($item_number - 1)) $goodsList[] = $reward[] = ['type' => GOODS_TYPE_8, 'gid' => $item_id, 'num' => 50 * ($item_number - 1)];
                            }else{
                                $goodsList[] = $reward[] = ['type' => GOODS_TYPE_8, 'gid' => $item_id, 'num' => 50 * $item_number];
                            }
                        }

                        $goodsList[] = [ 'type' => GOODS_TYPE_7, 'gid' => $param['gid'],'num' => -$gidnum ] ;
                        $this->player->goodsBridge($goodsList,'开启自选宝箱-神通',$this->player->getGoods($param['gid']) );

                        $result = [
                            'remian' => $this->player->getGoods($param['gid']),
                            'reward' => $reward,
                        ];
                    }
                }
            }
        }

        $this->sendMsg( $result );
    }

}