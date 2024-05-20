<?php
namespace App\Api\Controller\Equip;
use App\Api\Service\RoleService;
use App\Api\Service\EquipService;
use App\Api\Service\TaskService;
use App\Api\Table\ConfigEquipBase;
use App\Api\Controller\BaseController;


//装备上阵
class Apply extends BaseController
{

    public function index()
    {
        $equipTmp = $this->player->getData('equip_tmp');

        $result = [
            'index' 	=> '-1',
            'reward' 	=> [],
            'role' 	    => $this->player->getData('role'),
            'equip' 	=> EquipService::getInstance()->getEquipFmtData($this->player->getData('equip')),
            'equip_tmp' => EquipService::getInstance()->getEquipFmtData(array_values($this->player->getData('equip_tmp')))
        ];

        if($equipTmp)
        {
            $index = $this->param['index'];
            if(array_key_exists($index,$equipTmp))
            {
                $tmp = $equipTmp[$index];
                $oldEquip = $this->player->getData('equip',$tmp['type']);
                //装备上阵
                //装备栏无装备
                $this->player->setData('equip',$tmp['type'],$tmp);
                $this->player->setEquipTmp($index,[],'unset');
                
                //装备栏有装备
                if($oldEquip && !$this->param['auto']) $this->player->setEquipTmp( $oldEquip['index'] ,$oldEquip,'add');
            
                //是否自动回收装备
                $reward  = [];
                $reIndex = '';
                if($this->param['auto'] && $oldEquip)
                {
                    TaskService::getInstance()->setVal($this->player,71,1,'add');
                    $config = ConfigEquipBase::getInstance()->getOneByLevel($oldEquip['lv']);
                    $reward = $config['reward'];
                    foreach ($reward as $key => $value) 
                    {
                        if( $value['gid'] == LINGSHI) TaskService::getInstance()->setVal($this->player,45,$value['num'],'add');
                    }
                    $this->player->goodsBridge($reward,'装备自动回收');
                    $this->player->setRole('exp',EXP,'add');
                    
                    RoleService::getInstance()->checkLv($this->player);
                    $reIndex = $index;
                }
                
                TaskService::getInstance()->setVal($this->player,44,1,'add');
                TaskService::getInstance()->setVal($this->player,70,1,'set');
    
                $result = [
                    'index' 	=> $reIndex,
                    'type'      => $tmp['type'],
                    'reward' 	=> $reward,
                    'old' 	    => $oldEquip && !$this->param['auto'] ? $oldEquip['index'] :'',
                    'role' 	    => $this->player->getData('role'),
                    'equip' 	=> EquipService::getInstance()->getEquipFmtData($this->player->getData('equip')),
                    'equip_tmp' => EquipService::getInstance()->getEquipFmtData(array_values($this->player->getData('equip_tmp')))
                ];
            }
        }

        $this->sendMsg( $result );
    }

}