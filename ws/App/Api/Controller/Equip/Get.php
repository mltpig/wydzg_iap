<?php
namespace App\Api\Controller\Equip;
use App\Api\Utils\Consts;
use App\Api\Service\EquipService;
use App\Api\Service\TaskService;
use App\Api\Service\ComradeService;
use App\Api\Service\Module\MonthlyCardService;
use App\Api\Table\ConfigParam;
use App\Api\Controller\BaseController;
use App\Api\Service\TreeService;
use EasySwoole\Utility\SnowFlake;
//抽卡
class Get extends BaseController
{

    public function index()
    {
        
        $killConst  = $this->player->getArg(Consts::MAIN_Kill_COST);

        if($killConst)
        {
            // $timestamp  = $this->player->getArg(Consts::EQUIP_FALLING_TIME);
            // $result     = '当前频率异常待处理';
            // if(!$timestamp || time() - $timestamp > 3)
            // {
                
                $equipTmp = $this->player->getData('equip_tmp');
                $result = '当前存在待处理装备';
                if(!$equipTmp || 10 > count($equipTmp) )
                {
                    $cost = $this->param['multiple'] * $killConst;
                    $hasNum = $this->player->getGoods(XIANTAO);
                    $result = '仙桃数量不足';
    
                    if($hasNum >= $cost)
                    {
                        $cost = [ [ 'type' => GOODS_TYPE_1,'gid' => XIANTAO,'num' => -$cost ] ];
                        $this->player->goodsBridge($cost,'砍树消耗',$hasNum);
    
                        $treeLv = $this->player->getData('tree','lv');
                        $tmpList = [];
                        //前15次抽卡按照配置表出装备
                        $counter = $this->player->getArg(COUNTER_EQUIP);
                        TaskService::getInstance()->setVal($this->player,2,$this->param['multiple'],'add');
                        if( $counter >= 15)
                        {
                            //判断是否有贤士加成
                            $sum  = ComradeService::getInstance()->getLvStageByTalent($this->player,60004);
                            $option = [];
                            if($sum)
                            {
                                if($this->param['option1'] && $this->param['option2'] && $this->param['option1'] != $this->param['option2'])
                                {
                                    $option =  [ $this->param['option1'] => floor($sum / 2)  , $this->param['option2'] => floor($sum / 2) ];
                                }else{
                                    if($this->param['option1'] > 0) $option[ $this->param['option1'] ] = $sum;
                                    if($this->param['option2'] > 0) $option[ $this->param['option2'] ] = $sum;
                                }           
                            }

                            $roleLv = $this->player->getData('role','lv');
                            for ($i=0; $i < $this->param['multiple'] ; $i++)
                            { 
                                $newIndex = strval(SnowFlake::make(1,1));
                                $newTmp   = EquipService::getInstance()->extract($treeLv,$roleLv,$option,0);
                                $newTmp['index'] =  $newIndex;
    
                                $tmpList[] = $newTmp;
    
                                $this->player->setEquipTmp($newIndex,$newTmp,'add');
                            }
                            
                            $mainCost = mt_rand(3,5);

                        }else{
                            $newIndex = strval(SnowFlake::make(1,1));
                            $newTmp   = EquipService::getInstance()->getGuideExtract($counter);
                            $newTmp['index'] =  $newIndex;
    
                            $tmpList[] = $newTmp;
                            $this->player->setEquipTmp($newIndex,$newTmp,'add');
                            $this->player->setArg(COUNTER_EQUIP,1,'add');

                            $mainCost = 3;
                        }

                        $rewardList = [];
                        $max        = ConfigParam::getInstance()->getFmtParam('PVP_CHALLENGE_COST_LIMIT');
                        if(MonthlyCardService::getInstance()->getMonthlyCardExpire($this->player)) $max += 3;//月卡上限+3
                        if($this->player->getArg(Consts::LIFETTIME_CARD_TIME)) $max += 3;//终身卡上限+3
                        for ($i=0; $i < $this->param['multiple'] ; $i++)
                        { 
                            $reward = TreeService::getInstance()->getRandReward($treeLv);
                            if($reward && $reward[0]['gid'] == TIAOZHANQUAN)
                            {
                                if($this->player->getGoods(TIAOZHANQUAN) >= $max ) $reward = [];
                            }

                            if($reward)
                            {
                                foreach ($reward as $key => $value) 
                                {
                                    array_key_exists($value['gid'],$rewardList) ? $rewardList[$value['gid']]['num'] += $value['num'] : $rewardList[$value['gid']] = $value;
                                }
                            }
                        }

                        
                        if($rewardList) $this->player->goodsBridge($rewardList,'砍树掉落',$hasNum);

                        $this->player->setArg(Consts::MAIN_Kill_COST,$mainCost,'reset');

                        // $this->player->setArg(Consts::EQUIP_FALLING_TIME,time(),'reset');
    
                        $result = [
                            'reward'     => array_values($rewardList) ,
                            'remain'     => $this->player->getGoods(XIANTAO),
                            'kill_cost' => $mainCost,
                            'equip_tmp'  => EquipService::getInstance()->getEquipFmtData($tmpList),
                        ];
                    }
                }
    
            // }
        }
        $this->sendMsg( $result );
    }

}