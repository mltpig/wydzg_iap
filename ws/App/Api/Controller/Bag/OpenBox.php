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

//抽卡
class OpenBox  extends BaseController
{

    public function index()
    {
        // gid num target
        $param = $this->param;

        $result = 'target 格式错误';
        if(is_array($param['target']))
        {
            $result = '数量不足';
            if($this->player->getGoods($param['gid']) >= $param['num'])
            {
                $config = ConfigGoods::getInstance()->getOne($param['gid']);
                
                $result = '该物品不可开启';
                if($config && $config['type'] == 6)
                {
                    list( $map , $pool ) = ConfigGoodsBox::getInstance()->getAll($param['gid']);
                    $boxConfig = $map[ randTable($pool) ];
                    $goodsInfo = ConfigGoods::getInstance()->getOne($boxConfig['item_id']);
    
                    $result = '功能未解锁';
                    if($goodsInfo['type'] == 3)
                    {
                        $bag   = $this->player->getData('pet','bag');
                        $total = count($bag);
                        $free  =  $total - count(array_filter($bag));
    
                        $result = '副将栏已满';
                        if($free >= $param['num'])
                        {
                            $reward = $rewards = [];
                            for ($i=0; $i < $param['num']; $i++) 
                            { 
                                $boxConfig = $map[ randTable($pool) ];
                                $goodsInfo = ConfigGoods::getInstance()->getOne($boxConfig['item_id']);
                                switch ($goodsInfo['type']) 
                                {
                                    case 3:
                                        $bagid = PetService::getInstance()->checkFreeBag( $this->player->getData('pet','bag') );
                                        if($bagid == -1 ) break;
                                        $goodsList[] = $reward[] = ['type' => GOODS_TYPE_3,'gid' => $boxConfig['item_id'],'num' => 1];
                                        break;
                                    default:
                                        # code...
                                        break;
                                }
                            }
                            $goodsList[] = [ 'type' => GOODS_TYPE_6,'gid' => $param['gid'],'num' => -$param['num'] ];
                            $this->player->goodsBridge($goodsList,'开启宝箱-副将',$this->player->getGoods($param['gid']) );

                            foreach ($reward as $value) 
                            {
                                array_key_exists($value['gid'],$rewards) ? $rewards[ $value['gid'] ]['num']++ : $rewards[ $value['gid'] ] = $value ;
                            }
                            TaskService::getInstance()->setVal($this->player,36,1,'add');

                            $result = [
                                'remian' => $this->player->getGoods($param['gid']),
                                'reward' => array_values($rewards),
                                'head'  => $this->player->getData('head'),
                            ];
                        }
    
                    }else if($goodsInfo['type'] == 5){

                        $reward = $rewards = $goodsList = [];
                        for ($i=0; $i < $param['num']; $i++) 
                        {
                            $boxConfig = $map[ randTable($pool) ];
                            // 红颜成品*30 碎片*1
                            if($boxConfig['min_num'] == $boxConfig['max_num'])
                            {
                                $goodsList[] = $reward[] = ['type' => GOODS_TYPE_5, 'gid' => $boxConfig['item_id'], 'num' => $boxConfig['max_num']];
                            }
                        }
                        
                        $goodsList[] = [ 'type' => GOODS_TYPE_5,'gid' => $param['gid'],'num' => -$param['num'] ] ;
                        $this->player->goodsBridge($goodsList,'开启宝箱-红颜',$this->player->getGoods($param['gid']) );

                        foreach ($reward as $item)
                        {
                            if (isset($rewards[$item['gid']])) {
                                $rewards[$item['gid']]['num'] += $item['num'];
                            } else {
                                $rewards[$item['gid']] = $item;
                            }
                        }

                        $result = [
                            'remian' => $this->player->getGoods($param['gid']),
                            'reward' => array_values($rewards)
                        ];

                    }else if($goodsInfo['type'] == 23){

                        $reward = $rewards = $goodsList = [];
                        for ($i=0; $i < $param['num']; $i++) 
                        {
                            $boxConfig = $map[ randTable($pool) ];
                            if($boxConfig['min_num'] == $boxConfig['max_num'])
                            {
                                $goodsList[] = $reward[] = ['type' => GOODS_TYPE_23, 'gid' => $boxConfig['item_id'], 'num' => $boxConfig['max_num']];
                            }
                        }

                        $goodsList[] = [ 'type' => GOODS_TYPE_6,'gid' => $param['gid'],'num' => -$param['num'] ] ;
                        $this->player->goodsBridge($goodsList,'开启随机宝箱-刻印',$this->player->getGoods($param['gid']) );

                        foreach ($reward as $item)
                        {
                            if (isset($rewards[$item['gid']])) {
                                $rewards[$item['gid']]['num'] += $item['num'];
                            } else {
                                $rewards[$item['gid']] = $item;
                            }
                        }

                        $result = [
                            'remian' => $this->player->getGoods($param['gid']),
                            'reward' => array_values($rewards)
                        ];

                    }else if($goodsInfo['type'] == 8){

                        $reward = $rewards = $goodsList = [];
                        $item   = ConfigMagic::getInstance()->getItemId();

                        for ($i = 0; $i < $param['num']; $i++) 
                        {
                            $boxConfig = $map[ randTable($pool) ];

                            $id     = $item[$boxConfig['item_id']]['id'];
                            $bag    = $this->player->getData('magic','bag');

                            if(!array_key_exists($id,$bag))
                            {
                                $itemStone = [];
                                for($number = 1; $number <= $item[$boxConfig['item_id']]['stone_num']; $number++)
                                {
                                    $itemStone[$number] = 0;
                                }
                                $old = ['id' => $id, 'lv' => 1, 'stage' => 1, 'stone' => $itemStone];
                                $this->player->setMagic('bag',$id,$old,'multiSet');
                                
                                $reward[] = ['type' => GOODS_TYPE_8, 'gid' => $boxConfig['item_id'], 'num' => 1];
                            }else{
                                $goodsList[] = $reward[] = ['type' => GOODS_TYPE_8, 'gid' => $boxConfig['item_id'], 'num' => 50];
                            }
                        }

                        $goodsList[] = [ 'type' => GOODS_TYPE_6, 'gid' => $param['gid'],'num' => -$param['num'] ] ;
                        $this->player->goodsBridge($goodsList,'开启随机宝箱-神通',$this->player->getGoods($param['gid']) );

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