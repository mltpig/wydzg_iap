<?php
namespace App\Api\Controller\Pet;
use App\Api\Service\ComradeService;
use App\Api\Table\ConfigPets;
use App\Api\Table\ConfigParam;
use App\Api\Service\Module\PetService;
use App\Api\Controller\BaseController;

//放生
class Release extends BaseController
{

    public function index()
    {
        $bagid  = $this->param['id'];

        $active = $this->player->getData('pet','active');

        $result = '上阵中，无法释放';
        if($active != $bagid)
        {
            $bag    = $this->player->getData('pet','bag');

            $result = '未解锁';
            if(array_key_exists($bagid,$bag) && $bag[$bagid])
            {
                $result = '已上锁，无法释放';
                if(!$bag[$bagid]['lock'])
                {
                    $result = '只有一位武将，无法释放';
                    if(count(array_filter($bag)) > 1)
                    {
                        $config = ConfigPets::getInstance()->getOne( $bag[$bagid]['id'] );
                        $reNum  = $config['back_reward']['num'];
                        $lvCost = $config['level_cost'];
                        
                        $lvBackNum = PetService::getInstance()->getUpLvTotalCost($bag[$bagid]['lv']-1,$lvCost['num']);
                        // $petLv = $bag[$bagid]['lv'] - 1;
                        // $lvBackNum = $petLv > 1 ? ($lvCost['num'] + $lvCost['num'] *  $petLv ) * ( $petLv / 2 ) : 0;
                        $backParam =  ConfigParam::getInstance()->getFmtParam('PET_BACK_PARAM');
                        
                        $reNum += ceil( $lvBackNum * ( $backParam / 1000) ); 
                        
                        $reward = [ [ 'type' => GOODS_TYPE_1,'gid' => $lvCost['gid'],'num' => $reNum ] ];
                        $this->player->goodsBridge($reward,'副将释放',$this->player->getGoods($lvCost['gid']));

                        $this->player->setPet('bag',$bagid,[],'multiSet');

                        $comradeReward =  [];
                        $sum  = ComradeService::getInstance()->getLvStageByTalent($this->player,60001);
                        if($sum > 0)
                        {
                            $config['create_cost']['num'] = floor($config['create_cost']['num'] * $sum / 1000);
                            $comradeReward = [ $config['create_cost'] ];
                            $this->player->goodsBridge($comradeReward,'副将放逐','贤士加成');
                        }

                        $result = [ 
                            'pet' 	 => PetService::getInstance()->getPetFmtData($this->player),
                            'reward' => $reward,
                            'comrade_reward' => $comradeReward,
                        ];
                    }
                }
    
            }
        }

        $this->sendMsg( $result );
    }

}