<?php
namespace App\Api\Controller\Pet;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigPets;
use App\Api\Service\Module\PetService;
use App\Api\Controller\BaseController;
use App\Api\Service\ComradeService;

//升级
class UpStar extends BaseController
{

    public function index()
    {
        $bagid    = $this->param['id'];
        $mergeId  = $this->param['mergeId'];

        $active    = $this->player->getData('pet','active');
        $bag    = $this->player->getData('pet','bag');

        $result = '请选择有效框位';
        if($bagid != $mergeId)
        {
            $result = '上阵者不可被操作';
            if($active != $mergeId)
            {
                $result = '未解锁';
                if(array_key_exists($bagid,$bag) && $bag[$bagid] && array_key_exists($mergeId,$bag) && $bag[$mergeId])
                {
                    $old   = $bag[$bagid];
                    $merge = $bag[$mergeId];
        
                    $result = '非同类，不可合成';
                    if($old['id'] == $merge['id'])
                    {
                        $config = ConfigPets::getInstance()->getOne( $old['id'] );
            
                        $result = '已达到顶级';
                        if($config['star_limit'] > $old['star'])
                        {
                            //吞噬返还灵果
                            $reNum  = $config['back_reward']['num'];
                            $lvCost = $config['level_cost'];
                            $lvBackNum = PetService::getInstance()->getUpLvTotalCost($merge['lv']-1,$lvCost['num']);
                            $backParam = ConfigParam::getInstance()->getFmtParam('PET_BACK_PARAM');
                            $reNum += ceil( $lvBackNum * ( $backParam / 1000) ); 

                            $reward = [ [ 'type' => GOODS_TYPE_1,'gid' => $lvCost['gid'],'num' => $reNum ] ];
                            $this->player->goodsBridge($reward,'副将归一',$this->player->getGoods($lvCost['gid']) );
                            //星级合并
                            $star = $old['star'] + $merge['star'] + 1;
                            //技能判断 写死四个，后续塑魂再修改
                            if( $star > $config['star_limit'] ) $star = intval($config['star_limit']); 

                            $times = $star - $old['star'];
                            for ($i=0; $i < $times; $i++) 
                            { 
                                if(count($old['skill']) >= 4)
                                {
                                    $skillid = PetService::getInstance()->getUpSkillId($old['skill']);
                                    if($skillid) $old['skill'][$skillid] += 1;
                                }else{
                                    $old['skill'] += PetService::getInstance()->getSkillRandom(1,$old['skill']);
                                }
                            }

                            $old['star'] = $star;
                            $this->player->setPet('bag',$bagid,$old,'multiSet');
                            $this->player->setPet('bag',$mergeId,[],'multiSet');

                            $comradeReward =  [];
                            $sum  = ComradeService::getInstance()->getLvStageByTalent($this->player,60001);
                            if($sum > 0)
                            {
                                $config['create_cost']['num'] = floor($config['create_cost']['num'] * $sum / 1000);
                                $comradeReward = [ $config['create_cost'] ];
                                $this->player->goodsBridge($comradeReward,'副将放逐','贤士加成');
                            }

                            $result = [ 
                                'pet' 	   => PetService::getInstance()->getPetFmtData($this->player),
                                'reward' => [ ['type' => GOODS_TYPE_1 , 'gid' => $lvCost['gid'],'num' => $reNum] ],
                                'comrade_reward' => $comradeReward,
                            ];
                            
                        }
                    }
                }
            }
            
        }


        $this->sendMsg( $result );
    }

}