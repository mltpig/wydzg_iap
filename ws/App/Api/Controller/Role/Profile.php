<?php
namespace App\Api\Controller\Role;
use EasySwoole\Utility\SnowFlake;
use App\Api\Utils\Consts;
use App\Api\Table\ConfigRole;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigDoufaRobot;
use App\Api\Service\RoleService;
use App\Api\Service\PlayerService;
use App\Api\Service\EquipService;
use App\Api\Service\TacticalService;
use App\Api\Service\ComradeService;
use App\Api\Service\BattleService;
use App\Api\Service\Module\EquipmentService;
use App\Api\Service\Module\PetService;
use App\Api\Service\Module\SpiritService;
use App\Api\Service\Module\MagicService;
use App\Api\Controller\BaseController;

class Profile  extends BaseController
{

    public function index()
    {
        $param      = $this->param;
        $heid       = $param['player'];
        $site       = $param['site'];

        $config = ConfigDoufaRobot::getInstance()->getOne($site,$heid);
        if($config)
        {
            $npcData               = BattleService::getInstance()->getNpcBattleInitData($config);
            list($attr,$ratio)     = BattleService::getInstance()->getRolePanel($npcData);
            
            $config['user']['uid'] = strval(SnowFlake::make(rand(0,31),rand(0,127)));

            $result = [
                'site'      => $site,
                'user'      => $config['user'],
                'chara' 	=> [],
                'role'      => ['lv' => $config['rolelv']],
                'cloud' 	=> $config['cloud'],
                'equip'     => $config['equip'],
                'equipment' => [],
                'comrade'   => [],
                'pet' 	    => [],
                'ext' 		=> [],
                'spirit'    => [],
                'tactical'  => [],
                'battleattr'    => [
                    'attr'      => $attr,
                    'ratio'     => $ratio,
                ],
                'power'         => $config['power'],
                'magic'			=> [],
            ];
    
            $this->sendMsg($result);

        }else{
            $playerSer  = new PlayerService($heid,$site);

            $comrade    = $playerSer->getData('comrade');
            list($_sum , $attrSum) = ComradeService::getInstance()->getComradeAttrSum($comrade);
    
            $selfData              = BattleService::getInstance()->getBattleInitData($playerSer);
            list($attr,$ratio)     = BattleService::getInstance()->getRolePanel($selfData);
            $power                 = BattleService::getInstance()->getPower($selfData);
    
            $result = [
                'site'      => $site,
                'user'      => $playerSer->getUserInfo(),
                'chara' 	=> RoleService::getInstance()->getCharaFmt($playerSer),
                'role'      => $playerSer->getData('role'),
                'cloud' 	=> $playerSer->getData('cloud'),
                'equip'     => EquipService::getInstance()->getEquipFmtData($playerSer->getData('equip')),
                'equipment' => EquipmentService::getInstance()->getEquipmentFmtData($playerSer),
                'comrade'   => ['attr_sum' => $attrSum],
                'pet' 	    => PetService::getInstance()->getPetFmtData( $playerSer ),
                'ext' 		=> $playerSer->getData('ext'),
                'spirit'    => SpiritService::getInstance()->getSpiritFmtData( $playerSer,$playerSer->getArg( Consts::SPIRIT_AD_TAG )),
                'tactical'  => $playerSer->getData('tactical'),
                'battleattr'    => [
                    'attr'      => $attr,
                    'ratio'     => $ratio,
                ],
                'power'         => $power,
                'magic'			=> MagicService::getInstance()->getMagicFmtData( $playerSer ),
            ];
    
            $this->sendMsg($result);
        }
    }

}