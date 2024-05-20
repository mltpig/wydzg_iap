<?php
namespace App\Api\Controller\Equip;
use App\Api\Service\RoleService;
use App\Api\Service\TaskService;
use App\Api\Service\EquipService;
use App\Api\Table\ConfigEquipBase;
use App\Api\Controller\BaseController;
//回收
class Recovery extends BaseController
{

    public function index()
    {
        $equipTmp = $this->player->getData('equip_tmp');

        $result = [
            'reward' 	=> [],
            'role' 	    => $this->player->getData('role'),
            'equip' 	=> EquipService::getInstance()->getEquipFmtData($this->player->getData('equip')),
            'equip_tmp' => EquipService::getInstance()->getEquipFmtData(array_values($this->player->getData('equip_tmp')))
        ];

        if($equipTmp)
        {
            $result = '分解装备ID格式错误';
            $list = $this->param['index'];
            if(is_array($list))
            {
                $reward = [];
                foreach ($list as $key => $index) 
                {
                    if(array_key_exists($index,$equipTmp))
                    {
                        TaskService::getInstance()->setVal($this->player,71,1,'add');
        
                        $config = ConfigEquipBase::getInstance()->getOneByLevel($equipTmp[$index]['lv']);
                        foreach ($config['reward'] as $key => $value) 
                        {
                            if( $value['gid'] == LINGSHI) TaskService::getInstance()->setVal($this->player,45,$value['num'],'add');
                            
                            array_key_exists($value['gid'],$reward) ? $reward[$value['gid']]['num'] += $value['num']  : $reward[$value['gid']] = $value;
                        }

                        $this->player->goodsBridge($config['reward'],'装备手动回收');
                        $this->player->setEquipTmp($index,[],'unset');
                        $this->player->setRole('exp',EXP,'add');
                    }
                }

                RoleService::getInstance()->checkLv($this->player);
            
                $result = [ 
                    'role' 		=> $this->player->getData('role'),
                    'reward'    => array_values($reward),
                    'equip_tmp' => EquipService::getInstance()->getEquipFmtData(array_values($this->player->getData('equip_tmp')))
                ];
            }
        }

        $this->sendMsg( $result );
    }

}