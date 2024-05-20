<?php

namespace App\Api\Service;

use App\Api\Service\Module\MagicSkillService;
use App\Api\Service\Module\PetService;
use App\Api\Service\Module\SpiritService;
use App\Api\Service\Module\TowerService;
use App\Api\Service\Module\EquipmentService;
use App\Api\Service\Module\MagicService;
use App\Api\Table\ConfigCloud;
use App\Api\Table\ConfigCloudStage;
use App\Api\Table\ConfigSkill;
use App\Api\Table\ConfigRoleChara;
use EasySwoole\Component\CoroutineSingleTon;
use EasySwoole\EasySwoole\Core;

class BattleService
{
    use CoroutineSingleTon;

    const CRITICAL_HIT = 0;    //暴击


    const ATTACK_BACK = 1;   //反击


    const DOUBLE_ATTACK = 2; //连击

    const STUN = 3;//击晕

    const DODGE = 4;//闪避
    const TRIGGER_DOUBLE_ATTACK = 5;//连击触发

    const TRIGGER_ATTACK_BACK = 6;//反击触发

    const ATTACK_BACK_AND_DOUBLE_ATTACK = 7;//自己反击结束后，别人还有连击

    const REVIVE = 8;//触发复活

    const TRIGGER_REVIVE = 9;//触发复活

    const MAGIC_ATTACK = 10;//释放战技

    const MAGIC_DOUBLE_ATTACK = 11;//战技道法连击
    const TRIGGER_MAGIC_DOUBLE_ATTACK = 12;//触发道法连击
    const MAGIC_ATTACK_TRIGGER_DOUBLE_ATTACK = 13;//触发道法时 普通连击


    //战斗时各属性值
    private $battleAttr = [
        //基础属性
        'attack' => '0', 'hp' => '0', 'defence' => '0', 'speed' => '0',
        //加成  击晕    暴击    连击    闪避    反击    吸血
        'stun' => '0', 'critical_hit' => '0', 'double_attack' => '0', 'dodge' => '0', 'attack_back' => '0', 'life_steal' => '0',
        //抗性 击晕    暴击    连击    闪避    反击    吸血
        're_stun' => '0', 're_critical_hit' => '0', 're_double_attack' => '0', 're_dodge' => '0', 're_attack_back' => '0', 're_life_steal' => '0',
        //最终增伤 最终减伤 强化爆伤 弱化爆伤 强化治疗 弱化治疗
        'final_hurt' => '0', 'final_sub_hurt' => '0', 'fortify_critical_hit' => '0', 'weaken_critical_hit' => '0', 'fortify_cure' => '0', 'weaken_cure' => '0',
        //强化副将 弱化副将 无视战斗属性 无视战斗抗性
        'fortify_pet' => '0', 'weaken_pet' => '0', 'ignore_arr' => '0', 'ignore_arr_re' => '0',
        //强化道伤 被弱化道伤 道法连击 抗道法连击
        'fortify_magic' => '0', 'weaken_magic' => '0', 'magic_double_attack' => '0', 're_magic_double_attack' => '0',
    ];
    //用于技能百分比计算模板
    private $attrRatioFmt = [
        //基础属性具体值
        'attack' => '0', 'hp' => '0', 'defence' => '0', 'speed' => '0',
        //基础属性百分比值
        'ratio_attack' => '0', 'ratio_hp' => '0', 'ratio_defence' => '0', 'ratio_speed' => '0',
        //加成  击晕    暴击    连击    闪避    反击    吸血
        'stun' => '0', 'critical_hit' => '0', 'double_attack' => '0', 'dodge' => '0', 'attack_back' => '0', 'life_steal' => '0',
        //抗性 击晕    暴击    连击    闪避    反击    吸血
        're_stun' => '0', 're_critical_hit' => '0', 're_double_attack' => '0', 're_dodge' => '0', 're_attack_back' => '0', 're_life_steal' => '0',
        //最终增伤 最终减伤 强化爆伤 弱化爆伤 强化治疗
        'final_hurt' => '0', 'final_sub_hurt' => '0', 'fortify_critical_hit' => '0', 'weaken_critical_hit' => '0', 'fortify_cure' => '0', 'weaken_cure' => '0',
        //强化灵兽  弱化灵兽    强化战斗抗性    强化战斗属性
        'fortify_pet' => '0', 'weaken_pet' => '0', 'ratio_re' => '0', 'ratio_battle' => '0',
        //无视战斗属性 无视战斗抗性 强化道伤 被弱化道伤
        'ignore_arr' => '0', 'ignore_arr_re' => '0', 'fortify_magic' => '0', 'weaken_magic' => '0',
        //道法连击 抗道法连击
        'magic_double_attack' => '0', 're_magic_double_attack' => '0',
    ];
    //技能类型
    private $skillTypeMap = [
        //攻击  血量 防御 速度
        1001 => 'ratio_attack', 1002 => 'ratio_hp', 1003 => 'ratio_defence', 1004 => 'ratio_speed',
        //加成  击晕    暴击    连击    闪避    反击    吸血
        1005 => 'stun', 1006 => 'critical_hit', 1007 => 'double_attack', 1008 => 'dodge', 1009 => 'attack_back', 1010 => 'life_steal',
        //抗性 击晕    暴击    连击    闪避    反击    吸血
        1011 => 're_stun', 1012 => 're_critical_hit', 1013 => 're_double_attack', 1014 => 're_dodge', 1015 => 're_attack_back', 1016 => 're_life_steal',
        //最终增伤 最终减伤 强化爆伤 弱化爆伤 强化治疗 弱化治疗
        1017 => 'final_hurt', 1018 => 'final_sub_hurt', 1019 => 'fortify_critical_hit', 1020 => 'weaken_critical_hit', 1021 => 'fortify_cure', 1022 => 'weaken_cure',
        //强化灵兽  弱化灵兽    强化战斗抗性    强化战斗属性
        1023 => 'fortify_pet', 1024 => 'weaken_pet', 1025 => 'ratio_re', 1027 => 'ratio_battle',
        //具体值 攻击 生命 防御 敏捷
        2001 => 'attack', 2002 => 'hp', 2003 => 'defence', 2004 => 'speed'
    ];
    //属性点对于战力的系数
    private $attrWeight = [
        //攻击1=14  血量1=3 防御1=95 速度1=90
        'attack' => '14', 'hp' => '3', 'defence' => '95', 'speed' => '90',
        //加成  击晕    暴击    连击    闪避    反击    吸血
        //击晕0.001=180 暴击0.001=184 连击0.001=184 闪避0.001=261 反击0.001=234 吸血0.001=234
        'stun' => '180', 'critical_hit' => '184', 'double_attack' => '184', 'dodge' => '261', 'attack_back' => '234', 'life_steal' => '234',
        //抗性 击晕    暴击    连击    闪避    反击    吸血
        //抗击晕0.001=156 抗暴击0.001=141 抗连击0.001=152 抗闪避0.001=156 抗反击0.001=156 抗吸血0.001=156
        're_stun' => '156', 're_critical_hit' => '141', 're_double_attack' => '152', 're_dodge' => '156', 're_attack_back' => '156', 're_life_steal' => '156',
        //最终增伤 0.001=180    最终减伤0.001=180           强化爆伤0.001=184               弱化爆伤0.001=184
        'final_hurt' => '180', 'final_sub_hurt' => '180', 'fortify_critical_hit' => '184', 'weaken_critical_hit' => '184',
        //强化治疗0.001=141
        'fortify_cure' => '141',
    ];
    private $pataBuff = [];

    public function getBattleAttrFmt(): array
    {
        return $this->battleAttr;
    }

    public function getAttrRatioFmt(): array
    {
        return $this->attrRatioFmt;
    }

    public function getSkillTypeMap(): array
    {
        return $this->skillTypeMap;
    }

    public function setpataBuff(array $skill): void
    {
        $this->pataBuff = $skill;
    }

    public function getpataBuff(): array
    {
        return $this->pataBuff;
    }

    public function getBattleInitData(PlayerService $playerSer): array
    {
        $chara = $playerSer->getData('chara');
        return [
            'lv' => $playerSer->getData('role', 'lv'),  // 角色等级
            'cloud' => $playerSer->getData('cloud'),      // 附魂
            'equip' => $playerSer->getData('equip'),      // 装备
            'comrade' => $playerSer->getData('comrade'),    // 贤士
            'chara' => array_key_exists(2, $chara) ? $chara[2] : [],    // 模型
            'pet' => $playerSer->getData('pet'),        // 灵兽
            'spirit' => $playerSer->getData('spirit'),     // 精怪
            'tactical' => $playerSer->getData('tactical'),   // 阵法
            'equipment' => $playerSer->getData('equipment'),   // 精炼
            'magic' => $playerSer->getData('magic'),   // 神通
            'test' => $playerSer->getTmp('attribute'),    //测试添加数据
        ];
    }

    public function getNpcBattleInitData(array $param): array
    {
        return [
            'lv' => $param['rolelv'],
            'cloud' => $param['cloud'],
            'equip' => $param['equip'],
            'comrade' => [],
            'chara' => [],
            'pet' => [],
            'spirit' => [],
            'tactical' => [],
            'equipment' => [],
            'magic' => [],
        ];
    }

    public function getBattleShowData(PlayerService $playerSer): array
    {
        $pet = $playerSer->getData('pet');
        return [
            'cloud' => $playerSer->getData('cloud', 'apply'),      // 附魂
            'pet' => PetService::getInstance()->getPetGoIds($pet), // 灵兽
            'spirit' => [],
            'tactical' => [],
        ];
    }

    public function getNpcBattleShowData(array $param): array
    {
        return [
            'cloud' => -1,      // 附魂
            'pet' => ['active' => -1, 'help' => -1],      // 灵兽
            'spirit' => [],
            'tactical' => [],
        ];
    }

    public function getPower(array $detail): string
    {
        $attrWeight = $this->attrWeight;

        $sum = '0';
        $attr = $this->getRoleAttr($detail);
        foreach ($attr as $attrname => $attrvalue) {
            if (!array_key_exists($attrname, $attrWeight)) continue;
            $sum = add($sum, mul($attrWeight[$attrname], $attrvalue));
        }

        return $sum;
    }

    public function getRoleAttr(array $detail): array
    {
        $attr = $this->getBattleAttrFmt();
        $ratio = $this->getAttrRatioFmt();

        RoleService::getInstance()->getRoleAttrAdd($attr, $detail['lv']);
        EquipService::getInstance()->getEquipAttrAdd($attr, $detail['equip']);
        $this->getCloudAttrAdd($attr, $detail['cloud'], 'stage');
        ComradeService::getInstance()->getComradeAttrAdd($attr, $detail['comrade'], $ratio);
        $this->getCharaAdd($ratio, $detail['chara']);
        PetService::getInstance()->getPetAdd($ratio, isset($detail['pet']) ? $detail['pet'] : []);
        SpiritService::getInstance()->getSpiritAttrAdd($attr, $detail['spirit'], $ratio);
        TacticalService::getInstance()->getTacticalAttrAdd($attr, $detail['tactical']);//处理阵法属性+天赋技能
        EquipmentService::getInstance()->getEquipmentAttrAdd($attr, $detail['equip'], $detail['equipment'], $ratio);
        MagicService::getInstance()->getMagicAttrAdd($attr, $detail['magic'], $ratio);


        //获取临时加载属性，只能在dev环境下使用
        if (Core::getInstance()->runMode() === 'dev') {
            if (isset($detail['test'])) {
                AttributeComputeService::getInstance()->getTestAttribute($attr, $detail['test']);
            }
        }

        if ($buff = $this->getpataBuff()) {
            $this->getTowerAttrAdd($attr, $buff, $ratio);
        }

        //基础属性百分比加成，最后计算
        AttributeComputeService::getInstance()->computeBaseAttr($attr, $ratio);

        return $attr;
    }

    //获取玩家角色面板信息
    public function getRolePanel(array $detail): array
    {
        $attr = $this->getBattleAttrFmt();
        $ratio = $this->getAttrRatioFmt();

        RoleService::getInstance()->getRoleAttrAdd($attr, $detail['lv']);
        EquipService::getInstance()->getEquipAttrAdd($attr, $detail['equip']);
        $this->getCloudAttrAdd($attr, $detail['cloud'], 'stage');
        ComradeService::getInstance()->getComradeAttrAdd($attr, $detail['comrade'], $ratio);
        $this->getCharaAdd($ratio, $detail['chara']);
        PetService::getInstance()->getPetAdd($ratio, isset($detail['pet']) ? $detail['pet'] : []);
        SpiritService::getInstance()->getSpiritAttrAdd($attr, $detail['spirit'], $ratio);
        TacticalService::getInstance()->getTacticalAttrAdd($attr, $detail['tactical']);//处理阵法属性+天赋技能
        EquipmentService::getInstance()->getEquipmentAttrAdd($attr, $detail['equip'], $detail['equipment'], $ratio);
        MagicService::getInstance()->getMagicAttrAdd($attr, $detail['magic'], $ratio);

        return [$attr, $ratio];
    }

    public function getCharaAdd(&$ratio, array $chara): void
    {
        if (!$chara) return;
        //1025=强化战斗抗性 1027=强化战斗属性
        $map = [1025 => 'ratio_re', 1027 => 'ratio_battle'];

        foreach ($chara as $charaid => $level) {
            $config = ConfigRoleChara::getInstance()->getActivityOne($charaid);
            if (!$config['skill']) continue;

            $skillConfig = ConfigSkill::getInstance()->getOne($config['skill']);
            $attrName = $map[$skillConfig['type'][0]];
            $ratio[$attrName] = add($ratio[$attrName], $skillConfig['params'][0][0] * $level);
        }

    }

    public function getCloudAttrAdd(&$attr, array $cloud, string $filter): void
    {
        //未使用
        if (!$cloud || $cloud['apply'] == -1) return;

        //0=无抗性 11=击晕 12=暴击 13=连击 14=闪避 15=反击 16=吸血
        $list = [0 => '', 11 => 're_stun', 12 => 're_critical_hit', 13 => 're_double_attack', 14 => 're_dodge', 15 => 're_attack_back', 16 => 're_life_steal'];
        $type = ConfigCloud::getInstance()->getOne($cloud['apply'])['prim_attack'];
        $attrName = $list[$type];

        if ($filter === 'stage') {
            $config = ConfigCloudStage::getInstance()->getOne($cloud['stage'], $cloud['lv']);
        } else {
            $config = ConfigCloudStage::getInstance()->getOneById($cloud['lv']);
        }

        $attr['attack'] = add($attr['attack'], $config['attack']);
        $attr['hp'] = add($attr['hp'], $config['hp']);
        $attr['defence'] = add($attr['defence'], $config['defence']);

        $attr['re_stun'] = add($attr['re_stun'], $config['basic_resist']);
        $attr['re_critical_hit'] = add($attr['re_critical_hit'], $config['basic_resist']);
        $attr['re_double_attack'] = add($attr['re_double_attack'], $config['basic_resist']);
        $attr['re_dodge'] = add($attr['re_dodge'], $config['basic_resist']);
        $attr['re_attack_back'] = add($attr['re_attack_back'], $config['basic_resist']);
        $attr['re_life_steal'] = add($attr['re_life_steal'], $config['basic_resist']);

        if ($attrName) $attr[$attrName] = add($attr[$attrName], $config['towards_resist']);
    }

    public function getTowerAttrAdd(&$attr, array $tower, &$ratio): void
    {
        //战斗场景镇妖塔
        if (!$tower) return;
        $detail = TowerService::getInstance()->getTowerAttrSum($tower);

        foreach ($detail as $attrName => $attrValue) {
            if (array_key_exists($attrName, $attr)) {
                $attr[$attrName] = add($attr[$attrName], $attrValue);
            } else {
                $ratio[$attrName] = add($ratio[$attrName], $attrValue);
            }
        }
    }


    public function run(array $self, array $enemy, int $roundLimit, &$selfShowData, &$enemyShowData, bool $isRobot): array
    {
        $selfAttr = $this->getRoleAttr($self);
        $selfPet = [];
        if ($self['pet']) {
            if ($self['pet']['active'] != -1) {
                $petId = $self['pet']['bag'][$self['pet']['active']]['id'];
                $petLv = $self['pet']['bag'][$self['pet']['active']]['lv'];
                $selfPet[0] = PetService::getInstance()->getPetActiveSkillAttr($petId, $petLv);
            }
        }
        //获取用户阵法数据
        $selfTactical = array();
        if (isset($self['tactical']) && $self['tactical']) {
            $selfTactical = TacticalService::getInstance()->getTacticalSkill($self['tactical']);
        }
        foreach ($selfTactical as $key => $lv) {
            $selfShowData['tactical'][] = ['id' => (int)$key, 'lv' => (int)$lv];
        }

        //获取用户精怪数据
        $selfSpiritList = array();
        if (isset($self['spirit']) && $self['spirit']) {
            $selfSpiritList = SpiritService::getInstance()->getSpiritList($self['spirit']);
        }

        $selfSpirit = array();
        foreach ($selfSpiritList as $key => $lv) {
            list($id, $value) = SpiritService::getInstance()->getSpiritSkill($key, $lv);
            $selfSpirit[$id] = $value;
            $selfShowData['spirit'][] = ['id' => (int)$key, 'lv' => (int)$value];
        }

        //获取用户神通
        $selfMagic = array('pet' => 0);
        $selfShowData['magicInitiative'] = -1;
        $selfShowData['magic'] = array();
        $selfAttr['magicInitiativeSkill'] = false;
        if (isset($self['magic']) && $self['magic']) {
            $magicList = MagicService::getInstance()->getMagicList($self['magic']);
            foreach ($magicList as $key => $value) {
                if ($value['id'] == 0) {
                    continue;
                }
                if ($key == 1) {
                    $selfAttr['magicInitiativeSkill'] = $value['skill_id'];
                    $selfShowData['magicInitiative'] = $value['id'];
                } elseif ($key == 2) {
                    $selfMagic['pet'] = $value['skill_id'];
                }
                $selfShowData['magic'][] = ['id' => (int)$value['skill_id'], 'lv' => (int)$value['lv']];
                $selfMagic[$value['skill_id']] = $value['lv'];
            }
        }
        $selfSkill = ['tactical' => $selfTactical, 'spirit' => $selfSpirit, 'magic' => $selfMagic];


        $enemyAttr = $isRobot ? $enemy : $this->getRoleAttr($enemy);
        //todo 测试
        //$enemyAttr['attack_back'] = 10000;
        //  $selfAttr['critical_hit'] = 10000;
        //$selfAttr['double_attack'] = 3000;
        //$enemyAttr['attack'] = 100;
//        $enemyAttr['re_attack_back'] = 1;
//        $enemyAttr['re_double_attack'] = 1;
//        $selfAttr['re_attack_back'] = 1;
        // $selfAttr['attack_back'] = 10000;
//        $selfAttr['life_steal'] = 1000;
//        $enemyAttr['life_steal'] = 3000;
//        $selfAttr['stun'] = 800;
//        $enemyAttr['stun'] = 800;
//        $selfAttr['double_attack'] = 1000;
//        $selfAttr['hp'] = 100000000;
//        $enemyAttr['hp'] = 100000000;
//        $selfAttr['hp_max'] = 100000000;
//        $enemyAttr['hp_max'] = 100000000;
        //$selfAttr['attack'] = 100;
        //$selfAttr['dodge'] = 5000;

        //获取敌方宠物技能数据,通过id
        $enemyPet = [];
        if ($enemy['pet']) {
            if ($enemy['pet']['active'] != -1) {
                $petId = $enemy['pet']['bag'][$enemy['pet']['active']]['id'];
                $petLv = $enemy['pet']['bag'][$enemy['pet']['active']]['lv'];
                $enemyPet[0] = PetService::getInstance()->getPetActiveSkillAttr($petId, $petLv);
            }
        }

        //获取用户阵法数据
        $enemyTactical = array();
        if (isset($enemy['tactical']) && $enemy['tactical']) {
            $enemyTactical = TacticalService::getInstance()->getTacticalSkill($enemy['tactical']);
        }
        foreach ($enemyTactical as $key => $lv) {
            $enemyShowData['tactical'][] = ['id' => $key, 'lv' => (int)$lv];
        }

        //获取用户精怪数据
        $enemySpiritList = array();
        if (isset($enemy['spirit']) && $enemy['spirit']) {
            $enemySpiritList = SpiritService::getInstance()->getSpiritList($enemy['spirit']);
        }

        $enemySpirit = array();
        foreach ($enemySpiritList as $key => $lv) {
            list($id, $value) = SpiritService::getInstance()->getSpiritSkill($key, $lv);
            $enemySpirit[$id] = $value;
            $enemyShowData['spirit'][] = ['id' => $key, 'lv' => (int)$value];
        }

        $enemyMagic = array('pet' => 0);
        $enemyAttr['magicInitiativeSkill'] = false;
        $enemyShowData['magicInitiative'] = -1;
        $enemyShowData['magic'] = array();
        if (isset($enemy['magic']) && $enemy['magic']) {
            $magicList = MagicService::getInstance()->getMagicList($enemy['magic']);
            foreach ($magicList as $key => $value) {
                if ($value['id'] == 0) {
                    continue;
                }
                if ($key == 1) {
                    $enemyAttr['magicInitiativeSkill'] = $value['skill_id'];
                    $enemyShowData['magicInitiative'] = $value['id'];
                } elseif ($key == 2) {
                    $enemyMagic['pet'] = $value['skill_id'];
                }
                $enemyShowData['magic'][] = ['id' => (int)$value['skill_id'], 'lv' => (int)$value['lv']];
                $enemyMagic[$value['skill_id']] = $value['lv'];
            }
        }

        $enemySkill = ['tactical' => $enemyTactical, 'spirit' => $enemySpirit, 'magic' => $enemyMagic];

        $selfAttr['hp_max'] = $selfAttr['hp'];
        $enemyAttr['hp_max'] = $enemyAttr['hp'];

        $selfAttr['buff'] = $selfAttr['debuff'] = $selfAttr['buff_pet'] = $selfAttr['debuff_pet'] = [];
        $enemyAttr['buff'] = $enemyAttr['debuff'] = $enemyAttr['buff_pet'] = $enemyAttr['debuff_pet'] = [];
        $selfAttr['buff_magic'] = $enemyAttr['buff_magic'] = [];
        $battleLog = array();

        $isContinue = true;
        $isSuccess = false;
        $roundCount = 1;
        $attackCount = 0;

        //计算出手顺序 写死
        $count = $selfAttr['speed'] >= $enemyAttr['speed'] ? 0 : 1;

        $tmp = array(
            //mybuff=>["type":1,"heiheval存在的回合数":3,"ceng":1层数]存储技能释放buff
            //                                伤害数据       加血数据         我带的buff
            'ismy' => true, 'type' => 0, 'shanghaiData' => [], 'buffdata' => [], 'mybuff' => [], 'id' => 0,
            'enemyShanghaiData' => [], 'enemyBuffdata' => [],
            //给敌人加的buff
            'enemybuff' => [],
            'spirit' => [], 'enemySpirit' => [], 'tactical' => [], 'enemyTactical' => [],
            'magic' => [], 'enemyMagic' => [],
            //双方扣除血量。self我方造成的伤害，enemy敌方造成的伤害
            'hurt' => ['self' => 0, 'enemy' => 0],

            //额外伤害 额外加血
            'extShanghaiData' => [], 'extBuffdata' => [],
            'extEnemyShanghaiData' => [], 'extEnemyBuffdata' => [],

            //状态
            'status' => [], 'enemyStatus' => [],

            'revice' => []//复活参数 type：0精怪，1，战技；  id索引


        );

        $selfRevive = 0;//我方复活次数
        $enemyRevive = 0;//敌方复活次数


        //先处理首回合开始触发技能，包括我方和敌方
        //处理我方
        $tmpCopy = $tmp;
        $tmpCopy['round'] = $roundCount;

        //初始化妖力
        MagicSkillService::getInstance()->init($selfAttr);
        MagicSkillService::getInstance()->init($enemyAttr);

        //todo::测试技能//40027, 40032, 40035
//        $selfSkill = ['spirit' => [40027 => 7, 40032 => 1, 40035 => 1, 40044 => 1, 40039 => 1], 'tactical' => [50003 => 1, 50004 => 7], 'magic' => [133302 => 1,133301=>9,132201=>2]];
//        $enemySkill = ['spirit' => [40027 => 7, 40032 => 1, 40035 => 1, 40026 => 1, 40039 => 1], 'tactical' => [50003 => 1, 50004 => 1], 'magic' => [133302 => 1,133301=>9,132201=>2]];
//        $enemyShowData['magicInitiative'] = 133302;
//        $enemyAttr['magicInitiativeSkill'] = 133302;
//        $selfShowData['magicInitiative'] = 133302;
//        $selfAttr['magicInitiativeSkill'] = 133302;

        if (isset($selfSkill['spirit'][40026]) || isset($selfSkill['magic'][133400])) $selfRevive++;
        if (isset($enemySkill['spirit'][40026]) || isset($enemySkill['magic'][133400])) $enemyRevive++;

        if ($count % 2 == 0) {
            $tmpCopy['ismy'] = true;
        } else {
            $tmpCopy['ismy'] = false;
        }

        $log = $tmpCopy;
        $log['type'] = 2;
        $log['hurt'] = ['self' => 0, 'enemy' => 0];
        if ($log['ismy']) {
            $log['isFirst'] = true;//先出手
            //处理我方
            SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfAttr, $enemyAttr, $selfAttr, $enemyAttr, 0);
            //每回合开始触发技能
            SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfAttr, $enemyAttr, $selfAttr, $enemyAttr, 1);
            //第n回合开始触发技能
            SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfAttr, $enemyAttr, $selfAttr, $enemyAttr, 2, 'b');

            $log['isFirst'] = false;
            //处理敌方
            SkillService::getInstance()->triggerSkill($log, $enemySkill, $enemyAttr, $selfAttr, $enemyAttr, $selfAttr, 0);
            SkillService::getInstance()->triggerSkill($log, $enemySkill, $enemyAttr, $selfAttr, $enemyAttr, $selfAttr, 1);
            //第n开始触发技能
            SkillService::getInstance()->triggerSkill($log, $enemySkill, $enemyAttr, $selfAttr, $enemyAttr, $selfAttr, 2, 'b');

        } else {
            //处理敌方
            $log['isFirst'] = true;//先出手
            SkillService::getInstance()->triggerSkill($log, $enemySkill, $enemyAttr, $selfAttr, $enemyAttr, $selfAttr, 0);
            SkillService::getInstance()->triggerSkill($log, $enemySkill, $enemyAttr, $selfAttr, $enemyAttr, $selfAttr, 1);
            //第n开始触发技能
            SkillService::getInstance()->triggerSkill($log, $enemySkill, $enemyAttr, $selfAttr, $enemyAttr, $selfAttr, 2, 'b');

            //处理我方
            $log['isFirst'] = false;
            SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfAttr, $enemyAttr, $selfAttr, $enemyAttr, 0);
            //每回合开始触发技能
            SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfAttr, $enemyAttr, $selfAttr, $enemyAttr, 1);
            //第n回合开始触发技能
            SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfAttr, $enemyAttr, $selfAttr, $enemyAttr, 2, 'b');

        }


        $extHurtNameList = ['extShanghaiData', 'extEnemyShanghaiData'];
        if (!$log['ismy']) {
            AttributeComputeService::getInstance()->limitHpExtSub($enemyAttr, $selfAttr, $log);
            $hurtList = [
                'self' => $log['hurt']['enemy'],
                'enemy' => $log['hurt']['self'],
            ];
            $log['hurt'] = $hurtList;
            $extHurtNameList = ['extEnemyShanghaiData', 'extShanghaiData'];
        } else {
            AttributeComputeService::getInstance()->limitHpExtSub($selfAttr, $enemyAttr, $log);
        }

        //处理血量
        if ($log['hurt']['self'] > 0 && $enemyAttr['hp'] > 0) {
            $this->computeEnemyHp($enemyAttr, $log['hurt']['self']);
            if ($enemyAttr['hp'] <= 0 && $enemyRevive > 0 && (!isset($enemyAttr['debuff']['freeze']) || isset($enemySkill['magic'][133400]))) {
                $log[$extHurtNameList[1]][count($log[$extHurtNameList[1]]) - 1]['type'][] = self::REVIVE;
            }
            MagicSkillService::getInstance()->addStaminaToAttacked($enemyAttr, $log['hurt']['self']);
        }

        if ($log['hurt']['enemy'] > 0 && $selfAttr['hp'] > 0) {
            $this->computeEnemyHp($selfAttr, $log['hurt']['enemy']);
            if ($selfAttr['hp'] <= 0 && $selfRevive > 0 && (!isset($selfAttr['debuff']['freeze']) || isset($selfSkill['magic'][133400]))) {
                $log[$extHurtNameList[0]][count($log[$extHurtNameList[0]]) - 1]['type'][] = self::REVIVE;
            }
            MagicSkillService::getInstance()->addStaminaToAttacked($selfAttr, $log['hurt']['enemy']);
        }

        unset($log['isFirst'], $log['hurt']);

        if ($log['ismy']) {
            $log['hp'] = ['self' => $selfAttr['hp'], 'enemy' => $enemyAttr['hp']];
            $log['stamina'] = ['self' => $selfAttr['stamina'], 'enemy' => $enemyAttr['stamina']];
        } else {
            $log['hp'] = ['enemy' => $selfAttr['hp'], 'self' => $enemyAttr['hp']];
            $log['stamina'] = ['self' => $enemyAttr['stamina'], 'enemy' => $selfAttr['stamina']];
        }

        $battleLog[] = $log;

        $myReviveTriggerPet = false;//复活触发宠物出手
        $enemyReviveTriggerPet = false;//复活触发宠物出手
        $myAttackTriggerPet = false;//反击触发宠物出手
        $enemyAttackTriggerPet = false;//反击触发宠物出手
        $myMagicTriggerPet = false;//道法触发宠物出手
        $enemyMagicTriggerPet = false;//道法触发宠物出手
        $myMagicTriggerRound = 0;
        $enemyMagicTriggerRound = 0;
        //获取技能22类型是否符合条件
        $selfType22Skill = SkillService::getInstance()->isTriggerSkillType22($selfSkill, $roundCount, $selfAttr);
        $enemyType22Skill = SkillService::getInstance()->isTriggerSkillType22($enemySkill, $roundCount, $enemyAttr);


        //先宠物再主角出手
        while ($isContinue) {

            $attackCount++;
            $tmp['round'] = $roundCount;
            //处理每回合开始触发技能，第n回合开始触发技能

            $tmpCopy = $tmp;
            if ($count % 2 == 0) {
                $tmpCopy['ismy'] = true;
            } else {
                $tmpCopy['ismy'] = false;
            }
            $log = $tmpCopy;
            $log['type'] = 2;
            $log['hurt'] = ['self' => 0, 'enemy' => 0];


            //计算灵兽buff及debuff+技能buff
            $selfPetBattleAttr = BattleService::getInstance()->getBattlePetEffectAttr($selfAttr, $tmp['round']);
            $enemyPetBattleAttr = BattleService::getInstance()->getBattlePetEffectAttr($enemyAttr, $tmp['round']);

            //属性可能存在buff影响，每次重新计算
            $selfBattleAttr = AttributeComputeService::getInstance()->getBattleAttr($selfPetBattleAttr, $enemyPetBattleAttr);
            $enemyBattleAttr = AttributeComputeService::getInstance()->getBattleAttr($enemyPetBattleAttr, $selfPetBattleAttr);


            if ($attackCount % 2 == 1 && $roundCount > 1) {

                if ($log['ismy']) {
                    $log['isFirst'] = true;//先出手
                    //我方
                    //每回合开始触发技能
                    SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfBattleAttr, $enemyBattleAttr, $selfAttr, $enemyAttr, 1);
                    //第n回合开始触发技能
                    SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfBattleAttr, $enemyBattleAttr, $selfAttr, $enemyAttr, 2, 'b');
                    //处理道法状态,处理血量
                    MagicSkillService::getInstance()->triggerMagicStatus($log, $selfAttr, $enemyAttr, $selfBattleAttr, $enemyBattleAttr);

                    //敌方
                    $log['isFirst'] = false;
                    //每回合开始触发技能
                    SkillService::getInstance()->triggerSkill($log, $enemySkill, $enemyBattleAttr, $selfBattleAttr, $enemyAttr, $selfAttr, 1);
                    //第n开始触发技能
                    SkillService::getInstance()->triggerSkill($log, $enemySkill, $enemyBattleAttr, $selfBattleAttr, $enemyAttr, $selfAttr, 2, 'b');
                    //处理负面状态,处理血量
                    MagicSkillService::getInstance()->triggerMagicStatus($log, $enemyAttr, $selfAttr, $enemyBattleAttr, $selfBattleAttr);
                } else {
                    //处理敌方
                    $log['isFirst'] = true;//先出手
                    SkillService::getInstance()->triggerSkill($log, $enemySkill, $enemyBattleAttr, $selfBattleAttr, $enemyAttr, $selfAttr, 1);
                    //第n开始触发技能
                    SkillService::getInstance()->triggerSkill($log, $enemySkill, $enemyBattleAttr, $selfBattleAttr, $enemyAttr, $selfAttr, 2, 'b');
                    //处理负面状态,处理血量
                    MagicSkillService::getInstance()->triggerMagicStatus($log, $enemyAttr, $selfAttr, $enemyBattleAttr, $selfBattleAttr);

                    //处理我方
                    $log['isFirst'] = false;
                    //每回合开始触发技能
                    SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfBattleAttr, $enemyBattleAttr, $selfAttr, $enemyAttr, 1);
                    //第n回合开始触发技能
                    SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfBattleAttr, $enemyBattleAttr, $selfAttr, $enemyAttr, 2, 'b');
                    //处理道法状态,处理血量
                    MagicSkillService::getInstance()->triggerMagicStatus($log, $selfAttr, $enemyAttr, $selfBattleAttr, $enemyBattleAttr);
                }


                $extHurtNameList = ['extShanghaiData', 'extEnemyShanghaiData'];
                if (!$log['ismy']) {
                    AttributeComputeService::getInstance()->limitHpExtSub($enemyAttr, $selfAttr, $log);
                    $hurtList = [
                        'self' => $log['hurt']['enemy'],
                        'enemy' => $log['hurt']['self'],
                    ];
                    $log['hurt'] = $hurtList;
                    $extHurtNameList = ['extEnemyShanghaiData', 'extShanghaiData'];
                } else {
                    AttributeComputeService::getInstance()->limitHpExtSub($selfAttr, $enemyAttr, $log);
                }


                //处理血量
                if ($log['hurt']['self'] > 0 && $enemyAttr['hp'] > 0) {
                    $this->computeEnemyHp($enemyAttr, $log['hurt']['self']);
                    if ($enemyAttr['hp'] <= 0 && $enemyRevive > 0 && (!isset($enemyAttr['debuff']['freeze']) || isset($enemySkill['magic'][133400]))) {
                        $log[$extHurtNameList[1]][count($log[$extHurtNameList[1]]) - 1]['type'][] = self::REVIVE;
                    }
                    MagicSkillService::getInstance()->addStaminaToAttacked($enemyAttr, $log['hurt']['self']);
                }


                if ($log['hurt']['enemy'] > 0 && $selfAttr['hp'] > 0) {
                    $this->computeEnemyHp($selfAttr, $log['hurt']['enemy']);
                    if ($selfAttr['hp'] <= 0 && $selfRevive > 0 && (!isset($selfAttr['debuff']['freeze']) || isset($selfSkill['magic'][133400]))) {
                        $log[$extHurtNameList[0]][count($log[$extHurtNameList[0]]) - 1]['type'][] = self::REVIVE;
                    }
                    MagicSkillService::getInstance()->addStaminaToAttacked($selfAttr, $log['hurt']['enemy']);
                }

                unset($log['isFirst'], $log['hurt']);

                if ($log['ismy']) {
                    $log['hp'] = ['self' => $selfAttr['hp'], 'enemy' => $enemyAttr['hp']];
                    $log['stamina'] = ['self' => $selfAttr['stamina'], 'enemy' => $enemyAttr['stamina']];
                } else {
                    $log['hp'] = ['enemy' => $selfAttr['hp'], 'self' => $enemyAttr['hp']];
                    $log['stamina'] = ['self' => $enemyAttr['stamina'], 'enemy' => $selfAttr['stamina']];
                }

                $battleLog[] = $log;
                //获取技能22类型是否符合条件
                if (!$selfType22Skill) $selfType22Skill = SkillService::getInstance()->isTriggerSkillType22($selfSkill, $roundCount, $selfAttr);
                if (!$enemyType22Skill) $enemyType22Skill = SkillService::getInstance()->isTriggerSkillType22($enemySkill, $roundCount, $enemyAttr);
            }

            //计算灵兽buff及debuff+技能buff
            $selfPetBattleAttr = BattleService::getInstance()->getBattlePetEffectAttr($selfAttr, $tmp['round']);
            $enemyPetBattleAttr = BattleService::getInstance()->getBattlePetEffectAttr($enemyAttr, $tmp['round']);

            //属性可能存在buff影响，每次重新计算
            $selfBattleAttr = AttributeComputeService::getInstance()->getBattleAttr($selfPetBattleAttr, $enemyPetBattleAttr);
            $enemyBattleAttr = AttributeComputeService::getInstance()->getBattleAttr($enemyPetBattleAttr, $selfPetBattleAttr);


            $tmpCopy = $tmp;
            $tmpCopy['round'] = $roundCount;
            if ($count % 2 == 0) {
                $tmpCopy['ismy'] = true;

                foreach ($selfPet as $id => $petValue) {
                    if ((($roundCount - $myMagicTriggerRound) % $petValue['b']) == 0 || $myReviveTriggerPet || $myAttackTriggerPet || $myMagicTriggerPet) {
                        if ($enemyAttr['hp'] <= 0) {
                            $myReviveTriggerPet = true;
                            continue;
                        }
                        if ($myMagicTriggerPet) {
                            $myMagicTriggerRound = $roundCount;
                        }

                        $log = $this->petAttack($tmpCopy, $petValue, $id, $selfSkill, $enemySkill, $selfRevive, $enemyRevive, $selfBattleAttr, $enemyBattleAttr, $selfAttr, $enemyAttr, $selfType22Skill);
                        $battleLog[] = $log;
                        $myReviveTriggerPet = false;
                        $myAttackTriggerPet = false;
                        $myMagicTriggerPet = false;
                    }
                }
                //对冰冻做出特殊处理
                $enemyFreeze = false;
                if ($enemyAttr['hp'] <= 0 && $enemyRevive > 0 && isset($enemyAttr['debuff']['freeze']) && !isset($enemySkill['magic'][133400])) {
                    $enemyFreeze = true;
                }else{
                    $this->buffClear($selfAttr, $roundCount);//清自己身上状态
                    $this->buffClearEnemy($enemyAttr, $roundCount);//清敌方自己身上状态
                    //我方出手
                    $tmpCopy['type'] = 0;
                    $this->mainAttack($selfAttr, $enemyAttr, $battleLog, $tmpCopy, $selfSkill,
                        $enemySkill, $selfPet, $enemyPet, $selfRevive, $enemyRevive, $myReviveTriggerPet,
                        $enemyAttackTriggerPet, $myMagicTriggerPet, $myMagicTriggerRound);
                }


            } else {
                $tmpCopy['ismy'] = false;

                foreach ($enemyPet as $id => $petValue) {
                    if ((($roundCount - $enemyMagicTriggerRound) % $petValue['b']) == 0 || $enemyReviveTriggerPet || $enemyAttackTriggerPet || $enemyMagicTriggerPet) {
                        if ($selfAttr['hp'] <= 0) {
                            $enemyReviveTriggerPet = true;
                            continue;
                        }
                        $log = $this->petAttack($tmpCopy, $petValue, $id, $enemySkill, $selfSkill, $enemyRevive, $selfRevive, $enemyBattleAttr, $selfBattleAttr, $enemyAttr, $selfAttr, $enemyType22Skill);
                        $battleLog[] = $log;
                        if ($enemyReviveTriggerPet) {
                            $enemyMagicTriggerRound = $roundCount;
                        }

                        $enemyReviveTriggerPet = false;
                        $enemyAttackTriggerPet = false;
                        $enemyMagicTriggerPet = false;
                    }
                }
                //对冰冻做出特殊处理
                $isFreeze = false;
                if ($selfAttr['hp'] <= 0 && $selfRevive > 0 && isset($selfAttr['debuff']['freeze']) && !isset($selfSkill['magic'][133400])) {
                    $isFreeze = true;
                }else{
                    $this->buffClear($enemyAttr, $roundCount);
                    $this->buffClearEnemy($selfAttr, $roundCount);//清敌方自己身上状态

                    //敌方出手
                    $tmpCopy['type'] = 0;
                    $this->mainAttack($enemyAttr, $selfAttr, $battleLog, $tmpCopy, $enemySkill,
                        $selfSkill, $enemyPet, $selfPet, $enemyRevive, $selfRevive, $enemyAttackTriggerPet,
                        $myReviveTriggerPet, $enemyMagicTriggerPet, $enemyMagicTriggerRound);
                }


            }

            $isRevive = false;//是否存在复活次数
            if ($enemyAttr['hp'] <= 0 && $enemyRevive > 0 && (!$enemyFreeze || isset($enemySkill['magic'][133400]))) {
                $isRevive = true;
            }

            //判断自己是否胜利
            if ($roundCount <= $roundLimit && (!$enemyAttr['hp'] && !$isRevive)) {
                $isSuccess = true;
                $isContinue = false;
            }
            //回合数
            if ($attackCount > 0 && $attackCount % 2 == 0) {
                $roundCount++;
            }

            //跳出战斗  回合数大于等限制  我方或地方人物全部阵亡
            if ($roundCount > $roundLimit) $isContinue = false;

            $isRevive = false;//是否存在复活次数
            if ($selfAttr['hp'] <= 0 && $selfRevive > 0 && (!$isFreeze || isset($selfSkill['magic'][133400]))) {
                $isRevive = true;
            }
            if (!$selfAttr['hp'] && !$isRevive) $isContinue = false;
            $count++;

        }

        return [$isSuccess, $battleLog, $selfAttr['hp_max'], $enemyAttr['hp_max']];
    }


    public function mainAttack(&$selfDetail, &$enemyDetail, &$battleLog, $tmp, $selfSkill, $enemySkill, $selfPet, $enemyPet, &$selfRevive, &$enemyRevive, &$myReviveTriggerPet, &$enemyAttackTriggerPet, &$myMagicTriggerPet, &$myMagicTriggerRound): void
    {
        $log = $tmp;
        $log['shanghaiData'] = ['type' => [], '_val' => '0'];
        //判断是否复活
        if ($selfDetail['hp'] <= 0 && $selfRevive > 0 && (!isset($selfDetail['debuff']['freeze']) || isset($selfSkill['magic'][133400]))) {
            unset($selfDetail['debuff']['stun']);
            SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfDetail, $enemyDetail, $selfDetail, $enemyDetail, 12);

            $selfRevive--;
            $log['shanghaiData']['type'][] = self::TRIGGER_REVIVE;
        }

        //判断敌方是否还有血量
        if ($enemyDetail['hp'] <= 0) {
            if ($enemyRevive > 0 && (!isset($enemyDetail['debuff']['freeze']) || isset($enemySkill['magic'][133400]))) {
                $log['hp'] = ['self' => $selfDetail['hp'], 'enemy' => $enemyDetail['hp']];
                if (!$log['shanghaiData']['type'] && !$log['shanghaiData']['_val']) {
                    $log['shanghaiData'] = [];
                }
                $log['stamina'] = ['self' => $selfDetail['stamina'], 'enemy' => $enemyDetail['stamina']];
                $battleLog[] = $log;
            }
            return;
        }

        //判断是否有击晕
        if (array_key_exists('stun', $selfDetail['debuff']) || array_key_exists('freeze', $selfDetail['debuff'])) {
            if (!$log['shanghaiData']['type'] && !$log['shanghaiData']['_val']) {
                $log['shanghaiData'] = [];
            }
            $log['hp'] = ['self' => $selfDetail['hp'], 'enemy' => $enemyDetail['hp']];
            $log['stamina'] = ['self' => $selfDetail['stamina'], 'enemy' => $enemyDetail['stamina']];
            $battleLog[] = $log;
            return;
        }

        //计算灵兽buff及debuff+技能buff
        $selfPetBattleAttr = BattleService::getInstance()->getBattlePetEffectAttr($selfDetail, $tmp['round']);
        $enemyPetBattleAttr = BattleService::getInstance()->getBattlePetEffectAttr($enemyDetail, $tmp['round']);

        //属性可能存在buff影响，每次重新计算
        $selfBattleAttr = AttributeComputeService::getInstance()->getBattleAttr($selfPetBattleAttr, $enemyPetBattleAttr);
        $enemyBattleAttr = AttributeComputeService::getInstance()->getBattleAttr($enemyPetBattleAttr, $selfPetBattleAttr);


        $isAttackMagic = false;
        if ($selfDetail['magicInitiativeSkill'] && MagicSkillService::getInstance()->isStaminaMax($selfDetail)) {
            //计算道法伤害
            $hurt = 0;
            $skillData = ['id' => $selfDetail['magicInitiativeSkill'], 'lv' => $selfSkill['magic'][$selfDetail['magicInitiativeSkill']]];
            //触发道法主动技能
            MagicSkillService::getInstance()->triggerSkill($log, $skillData, $selfBattleAttr, $enemyBattleAttr,
                $selfDetail, $enemyDetail, $hurt, $selfSkill, $myMagicTriggerPet);
            $log['magic'][] = 0;
            $isAttackMagic = true;
            $log['shanghaiData']['type'][] = self::MAGIC_ATTACK;
        } else {
            //计算普通伤害
            $hurt = AttributeComputeService::getInstance()->computeHit($selfBattleAttr['attack'],
                $enemyBattleAttr['defence'], $selfBattleAttr['final_hurt']);
            MagicSkillService::getInstance()->addStaminaToAttask($selfDetail);
        }

        $selfSkillCopy = $selfSkill;//处理132303技能，避免去除之后自动触发

        $isDodge = false;//是否闪避
        $log['hurt'] = ['self' => 0, 'enemy' => 0];
        //判定敌方闪避概率.如果是道法攻击必中
        if ($isAttackMagic || isset($enemyDetail['debuff']['stun']) || isset($enemyDetail['debuff']['freeze']) || $enemyBattleAttr['dodge'] < rand(1, 1000)) {
            if ($hurt > 0) {
                $log['shanghaiData']['_val'] = add($hurt, $log['shanghaiData']['_val']);
                //暴击
                if ($selfBattleAttr['critical_hit'] >= rand(1, 1000)) {
                    $this->triggerCriticalHit($hurt, $log, $selfBattleAttr['fortify_critical_hit'],
                        $selfSkill, $selfBattleAttr, $enemyBattleAttr, $selfDetail, $enemyDetail);
                }
                //是否击晕对方
                if ($selfBattleAttr['stun'] >= rand(1, 1000)) {
                    $this->triggerStun($log, $enemyBattleAttr, $selfSkill, $selfBattleAttr, $selfDetail, $enemyDetail);
                }
                //吸血
                AttributeComputeService::getInstance()->limitHpSub($enemyDetail, $hurt, $log);
                $this->triggerLifeSteal($selfBattleAttr, $hurt, $selfDetail, $log);
                //计算伤害,扣除血量
                $this->computeEnemyHp($enemyDetail, $hurt);
            }
        } else {
            $hurt = 0;
            $isDodge = true;
            $log['shanghaiData']['type'][] = self::DODGE;
            //每次闪避
            $log['isFirst'] = false;
            SkillService::getInstance()->triggerSkill($log, $enemySkill, $enemyBattleAttr, $selfBattleAttr,
                $enemyDetail, $selfDetail, 10);
            unset($log['isFirst']);
            SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfBattleAttr, $enemyBattleAttr, $selfDetail, $enemyDetail, 24);
        }

        if ($hurt > 0) {
            MagicSkillService::getInstance()->addStaminaToAttacked($enemyDetail, $hurt);
        }

        //释放兵法时
        if ($isAttackMagic) {
            SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfBattleAttr, $enemyBattleAttr,
                $selfDetail, $enemyDetail, 19);
        }

        //攻击时,触发技能
        SkillService::getInstance()->attackTriggerSkill($log, $selfSkill, $selfBattleAttr, $enemyBattleAttr,
            $selfDetail, $enemyDetail);

        //每次被攻击,触发技能
        $log['isFirst'] = false;
        SkillService::getInstance()->triggerSkill($log, $enemySkill, $enemyBattleAttr, $selfBattleAttr,
            $enemyDetail, $selfDetail, 11);
        unset($log['isFirst']);
        SkillService::getInstance()->attackedTriggerSkill($log, $enemySkill, $selfBattleAttr, $enemyBattleAttr,
            $selfDetail, $enemyDetail);

        if ($enemyDetail['hp'] <= 0 && $enemyRevive > 0 && (!isset($enemyDetail['debuff']['freeze']) || isset($enemySkill['magic'][133400]))) {
            $log['shanghaiData']['type'][] = self::REVIVE;
            $log['extShanghaiData'] = [];
        }
        AttributeComputeService::getInstance()->limitHpExtSub($selfDetail, $enemyDetail, $log);

        //处理血量
        if ($log['hurt']['self'] > 0 && $enemyDetail['hp'] > 0) {
            $this->computeEnemyHp($enemyDetail, $log['hurt']['self']);
            if ($enemyDetail['hp'] <= 0 && $enemyRevive > 0 && (!isset($enemyDetail['debuff']['freeze']) || isset($enemySkill['magic'][133400]))) {
                $log['extShanghaiData'][count($log['extShanghaiData']) - 1]['type'][] = self::REVIVE;
            }
            MagicSkillService::getInstance()->addStaminaToAttacked($enemyDetail, $log['hurt']['self']);
        }
        if ($log['hurt']['enemy'] > 0 && $selfDetail['hp'] > 0) {
            $this->computeEnemyHp($selfDetail, $log['hurt']['enemy']);
            if ($selfDetail['hp'] <= 0 && $selfRevive > 0 && (!isset($selfDetail['debuff']['freeze']) || isset($selfSkill['magic'][133400]))) {
                $log['extEnemyShanghaiData'][count($log['extEnemyShanghaiData']) - 1]['type'][] = self::REVIVE;
            }
            MagicSkillService::getInstance()->addStaminaToAttacked($selfDetail, $log['hurt']['enemy']);
        }
        unset($log['hurt']);


        $log['hp'] = ['self' => $selfDetail['hp'], 'enemy' => $enemyDetail['hp']];
        $log['stamina'] = ['self' => $selfDetail['stamina'], 'enemy' => $enemyDetail['stamina']];
        $battleLog[] = $log;

        $selfSkill = $selfSkillCopy;

        if ($enemyDetail['hp'] > 0) {

            //判断道法连击
            if ($isAttackMagic && $selfDetail['hp'] > 0 && $enemyDetail['hp'] > 0 && !isset($selfDetail['debuff']['stun']) && !isset($selfDetail['debuff']['freeze']) && $selfBattleAttr['magic_double_attack'] >= rand(1, 1000)) {
                $this->triggerMagicDoubleAttack($battleLog, $tmp, $selfDetail, $enemyDetail, $selfBattleAttr, $enemyBattleAttr, $selfSkill, $enemySkill, $selfRevive, $enemyRevive);
            }


            $isAttackBack = false;
            if ($enemyDetail['hp'] > 0 && !isset($enemyDetail['debuff']['stun']) && !isset($enemyDetail['debuff']['freeze']) && $enemyBattleAttr['attack_back'] >= rand(1, 1000)) {
                //触发反击
                $this->triggerEnemyAttackBack($selfBattleAttr['attack_back'],
                    floor($enemyBattleAttr['attack_back'] / 2), $selfDetail, $enemyDetail, $selfBattleAttr,
                    $enemyBattleAttr, $battleLog, $tmp, $selfSkill, $enemySkill, $selfPet, $enemyPet, $selfRevive,
                    $enemyRevive, $selfType22Skill, $enemyType22Skill, $myAttackTriggerPet, $enemyAttackTriggerPet);
                $isAttackBack = true;
            }

            if (!$isDodge && !$isAttackBack && $selfDetail['hp'] > 0 && $enemyDetail['hp'] > 0 && !isset($selfDetail['debuff']['stun']) && !isset($selfDetail['debuff']['freeze']) && $selfBattleAttr['double_attack'] >= rand(1, 1000)) {
                if ($isAttackMagic) {
                    $isFirst = true;
                } else {
                    $isFirst = false;
                }
                //触发连击
                $this->triggerDoubleAttack(floor($selfBattleAttr['double_attack'] / 2), $selfDetail,
                    $enemyDetail, $enemyBattleAttr, $selfBattleAttr, $battleLog, $tmp, $selfSkill, $enemySkill,
                    $selfPet, $enemyPet, $selfRevive, $enemyRevive, $myReviveTriggerPet, $enemyAttackTriggerPet,
                    $myMagicTriggerPet, $myMagicTriggerRound, $isFirst);
            }
        }

        //触发副将攻击
        if ($enemyDetail['hp'] > 0 && $myMagicTriggerPet) {
            $log = $this->petAttack($tmp, $selfPet[0], 0, $selfSkill, $enemySkill, $selfRevive, $enemyRevive, $selfBattleAttr, $enemyBattleAttr, $selfDetail, $enemyDetail, $selfType22Skill);

            $myMagicTriggerRound = $log['round'];

            $battleLog[] = $log;
            $myMagicTriggerPet = 0;//是否触发副将出手
            $myReviveTriggerPet = false;
            $myMagicTriggerPet = false;
        }

    }

    //触发敌人反击
    public function triggerEnemyAttackBack(int $selfAttackBack, int $enemyAttackBack, &$selfDetail, &$enemyDetail, &$selfBattleAttr, &$enemyBattleAttr, &$battleLog, $tmp, $selfSkill, $enemySkill, $selfPet, $enemyPet, $selfRevive, $enemyRevive, &$selfType22Skill, &$enemyType22Skill, &$myAttackTriggerPet, &$enemyAttackTriggerPet)
    {
        if ($enemyDetail['hp'] <= 0) {
            return;
        }
        $enemySkillCopy = $enemySkill;
        //计算灵兽buff及debuff+技能buff
        $selfPetBattleAttr = BattleService::getInstance()->getBattlePetEffectAttr($selfDetail, $tmp['round']);
        $enemyPetBattleAttr = BattleService::getInstance()->getBattlePetEffectAttr($enemyDetail, $tmp['round']);

        //属性可能存在buff影响，每次重新计算
        $selfBattleAttr = AttributeComputeService::getInstance()->getBattleAttr($selfPetBattleAttr, $enemyPetBattleAttr);
        $enemyBattleAttr = AttributeComputeService::getInstance()->getBattleAttr($enemyPetBattleAttr, $selfPetBattleAttr);


        $battleLog[count($battleLog) - 1]['shanghaiData']['type'][] = self::ATTACK_BACK;//代表下一条反击
        $tmp['ismy'] = !$tmp['ismy'];
        $log = $tmp;
        $log['hurt'] = ['self' => 0, 'enemy' => 0];
        //计算普通伤害
        $backHurt = AttributeComputeService::getInstance()->computeHit($enemyBattleAttr['attack'],
            $selfBattleAttr['defence'], $enemyBattleAttr['final_hurt']);
        $log['shanghaiData'] = ['type' => [self::TRIGGER_ATTACK_BACK], '_val' => $backHurt];//代表这一条为反击触发
        $isDodge = false;
        //我方是否触发闪避
        if (isset($selfDetail['debuff']['stun']) || isset($selfDetail['debuff']['freeze']) || $selfBattleAttr['dodge'] < rand(1, 1000)) {
            //敌方是否触发暴击
            if ($enemyBattleAttr['critical_hit'] >= rand(1, 1000)) {
                $this->triggerCriticalHit($backHurt, $log, $enemyBattleAttr['fortify_critical_hit'],
                    $enemySkill, $enemyBattleAttr, $selfBattleAttr, $enemyDetail, $selfDetail);
            }
            //敌方是否触发击晕
            if ($enemyBattleAttr['stun'] >= rand(1, 1000)) {
                $this->triggerStun($log, $selfBattleAttr, $enemySkill, $enemyBattleAttr, $enemyDetail, $selfDetail);
            }
            //吸血
            AttributeComputeService::getInstance()->limitHpSub($selfDetail, $backHurt, $log);
            $this->triggerLifeSteal($enemyBattleAttr, $backHurt, $enemyDetail, $log);
            //计算伤害
            $this->computeEnemyHp($selfDetail, $backHurt);
            MagicSkillService::getInstance()->addStaminaToAttacked($selfDetail, $backHurt);
        } else {
            $isDodge = true;
            $log['shanghaiData']['type'][] = self::DODGE;
            $log['shanghaiData']['_val'] = 0;

            //每次闪避
            $log['isFirst'] = false;
            SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfBattleAttr, $enemyBattleAttr,
                $selfDetail, $enemyDetail, 10);
            unset($log['isFirst']);
            SkillService::getInstance()->triggerSkill($log, $enemySkill, $enemyBattleAttr, $selfBattleAttr, $enemyDetail, $selfDetail, 24);

        }

        if (!$isDodge) {//反击命中后
            SkillService::getInstance()->triggerSkill($log, $enemySkill, $enemyBattleAttr, $selfBattleAttr,
                $enemyDetail, $selfDetail, 18);
        }

        //攻击时,触发技能
        SkillService::getInstance()->attackTriggerSkill($log, $enemySkill, $enemyBattleAttr, $selfBattleAttr,
            $enemyDetail, $selfDetail);
        //反击时
        SkillService::getInstance()->triggerSkill($log, $enemySkill, $enemyBattleAttr, $selfBattleAttr,
            $enemyDetail, $selfDetail, 6, false, $enemyAttackTriggerPet);
        //每次被攻击,触发技能
        $log['isFirst'] = false;
        SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfBattleAttr, $enemyBattleAttr,
            $selfDetail, $enemyDetail, 11);
        unset($log['isFirst']);
        SkillService::getInstance()->attackedTriggerSkill($log, $selfSkill, $enemyBattleAttr, $selfBattleAttr,
            $enemyDetail, $selfDetail);

        if ($selfDetail['hp'] <= 0 && $selfRevive > 0 && (!isset($selfDetail['debuff']['freeze']) || isset($selfSkill['magic'][133400]))) {
            $log['shanghaiData']['type'][] = self::REVIVE;
            $log['extShanghaiData'] = [];
        }

        //处理血量
        AttributeComputeService::getInstance()->limitHpExtSub($enemyDetail, $selfDetail, $log);
        if ($log['hurt']['self'] > 0 && $selfDetail['hp'] > 0) {
            $this->computeEnemyHp($selfDetail, $log['hurt']['self']);
            if ($selfDetail['hp'] <= 0 && $selfRevive > 0 && (!isset($selfDetail['debuff']['freeze']) || isset($selfSkill['magic'][133400]))) {
                $log['extShanghaiData'][count($log['extShanghaiData']) - 1]['type'][] = self::REVIVE;
            }
            MagicSkillService::getInstance()->addStaminaToAttacked($selfDetail, $log['hurt']['self']);
        }

        if ($log['hurt']['enemy'] > 0 && $enemyDetail['hp'] > 0) {
            $this->computeEnemyHp($enemyDetail, $log['hurt']['enemy']);
            if ($enemyDetail['hp'] <= 0 && $enemyRevive > 0 && (!isset($enemyDetail['debuff']['freeze']) || isset($enemySkill['magic'][133400]))) {
                $log['extEnemyShanghaiData'][count($log['extEnemyShanghaiData']) - 1]['type'][] = self::REVIVE;
            }
            MagicSkillService::getInstance()->addStaminaToAttacked($enemyDetail, $log['hurt']['enemy']);
        }
        unset($log['hurt']);


        $log['hp'] = ['self' => $enemyDetail['hp'], 'enemy' => $selfDetail['hp']];
        $log['stamina'] = ['self' => $enemyDetail['stamina'], 'enemy' => $selfDetail['stamina']];
        $battleLog[] = $log;
        $enemySkill = $enemySkillCopy;
        if ($selfDetail['hp'] > 0) {
            //代表触发副将出手
            if ($enemyAttackTriggerPet > 0 && isset($enemyPet[0])) {
                $log = $this->petAttack($tmp, $enemyPet[0], 0, $enemySkill, $selfSkill, $enemyRevive, $selfRevive, $enemyBattleAttr, $selfBattleAttr, $enemyDetail, $selfDetail, $enemyType22Skill, $enemyAttackTriggerPet);
                $battleLog[] = $log;
                $enemyAttackTriggerPet = false;//是否触发副将出手
            }

            if ($selfDetail['hp'] > 0 && $enemyDetail['hp'] > 0 && !isset($selfDetail['debuff']['stun']) && !isset($selfDetail['debuff']['freeze']) && $selfAttackBack >= rand(1, 1000)) {
                $this->triggerEnemyAttackBack($enemyAttackBack, floor($selfAttackBack / 2), $enemyDetail, $selfDetail,
                    $enemyBattleAttr, $selfBattleAttr, $battleLog, $tmp, $enemySkill, $selfSkill, $enemyPet,
                    $selfPet, $selfRevive, $enemyRevive, $enemyType22Skill, $selfType22Skill, $enemyAttackTriggerPet, $myAttackTriggerPet);
            }
        }
    }


    //触发暴击
    public function triggerCriticalHit(&$hurt, &$log, $selfFortify, &$selfSkill, &$selfArr, &$enemyArr, &$selfDetail, &$enemyDetail): void
    {
        AttributeComputeService::getInstance()->computeCriticalHit($hurt, $selfFortify);
        $log['shanghaiData']['type'][] = self::CRITICAL_HIT;
        $log['shanghaiData']['_val'] = $hurt;
        SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfArr, $enemyArr,
            $selfDetail, $enemyDetail, 4);
        MagicSkillService::getInstance()->triggerSpecialMagicStatus($log, $selfDetail, $enemyDetail, $selfArr, $enemyArr, 'vulnerability', $hurt, $selfSkill);
    }

    //触发击晕
    public function triggerStun(&$log, &$enemyArr, $selfSkill, &$selfArr, &$selfDetail, &$enemyDetail): void
    {

        $log['shanghaiData']['type'][] = self::STUN;

        if (!isset($enemyDetail['debuff']['stun']) || $enemyDetail['debuff']['stun'] <= 2) {
            $enemyDetail['debuff']['stun'] = 2;
        }

        $specialBuff = ['type' => self::STUN, 'num' => $enemyDetail['debuff']['stun']];
        if (isset($log['shanghaiData']['specialBuff'])) {
            $log['shanghaiData']['specialBuff'][] = [$specialBuff];
        } else {
            $log['shanghaiData']['specialBuff'] = [$specialBuff];
        }

        SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfArr, $enemyArr, $selfDetail, $enemyDetail, 5);

    }

    //触发吸血
    public function triggerLifeSteal($selfBattleAttr, $hurt, &$selfDetail, &$log): void
    {

        if ($selfBattleAttr['life_steal'] > 0 && $selfDetail['hp'] < $selfDetail['hp_max']) {
            $lifeStealAdd = AttributeComputeService::getInstance()->computeLifeSteal($hurt,
                $selfBattleAttr['fortify_cure'], $selfBattleAttr['life_steal']);
            //计算吸血效果 计算敌我双方血量
            if ($lifeStealAdd > 0) {
                $totalHp = add($selfDetail['hp'], $lifeStealAdd);

                if ($totalHp >= $selfDetail['hp_max']) {
                    $lifeStealAdd = sub($selfDetail['hp_max'], $selfDetail['hp']);
                    $selfDetail['hp'] = $selfDetail['hp_max'];
                } else {
                    $selfDetail['hp'] = $totalHp;
                }
                $log['buffdata'] = ['type' => 0, '_val' => $lifeStealAdd];
            }
        }
    }

    public function triggerDoubleAttack(int $doubleAttack, &$selfDetail, &$enemyDetail, &$enemyBattleAttr, &$selfBattleAttr, &$battleLog, $tmp, $selfSkill, $enemySkill, $selfPet, $enemyPet, $selfRevive, $enemyRevive, &$myReviveTriggerPet, &$enemyAttackTriggerPet, &$myMagicTriggerPet, &$myMagicTriggerRound, $isFirst = false): void
    {
        $selfSkillCopy = $selfSkill;
        //计算灵兽buff及debuff+技能buff
        $selfPetBattleAttr = BattleService::getInstance()->getBattlePetEffectAttr($selfDetail, $tmp['round']);
        $enemyPetBattleAttr = BattleService::getInstance()->getBattlePetEffectAttr($enemyDetail, $tmp['round']);

        //属性可能存在buff影响，每次重新计算
        $selfBattleAttr = AttributeComputeService::getInstance()->getBattleAttr($selfPetBattleAttr, $enemyPetBattleAttr);
        $enemyBattleAttr = AttributeComputeService::getInstance()->getBattleAttr($enemyPetBattleAttr, $selfPetBattleAttr);

        //反击触发连击
        //$battleLog[count($battleLog) - 1]['shanghaiData']['type'][] = self::ATTACK_BACK_AND_DOUBLE_ATTACK;
        $log = $tmp;
        $log['hurt'] = ['self' => 0, 'enemy' => 0];
        //反击触发连击
        if ($isFirst) {
            $log['shanghaiData'] = ['type' => [self::TRIGGER_DOUBLE_ATTACK, self::MAGIC_ATTACK_TRIGGER_DOUBLE_ATTACK], '_val' => '0'];//为连击触发的数据
        } else {
            $battleLog[count($battleLog) - 1]['shanghaiData']['type'][] = self::DOUBLE_ATTACK;//下一条为连击
            $log['shanghaiData'] = ['type' => [self::TRIGGER_DOUBLE_ATTACK], '_val' => '0'];//为连击触发的数据
        }

        //计算普通伤害
        $hurt = AttributeComputeService::getInstance()->computeHit($selfBattleAttr['attack'],
            $enemyBattleAttr['defence'], $selfBattleAttr['final_hurt']);

        $isDodge = false;
        //判定敌方闪避概率
        if (isset($enemyDetail['debuff']['stun']) || isset($enemyDetail['debuff']['freeze']) || $enemyBattleAttr['dodge'] < rand(1, 1000)) {
            //暴击
            if ($selfBattleAttr['critical_hit'] >= rand(1, 1000)) {
                $this->triggerCriticalHit($hurt, $log, $selfBattleAttr['fortify_critical_hit'],
                    $selfSkill, $selfBattleAttr, $enemyBattleAttr, $selfDetail, $enemyDetail);
            }

            //是否击晕对方
            if (!isset($selfDetail['debuff']['stun']) && !isset($selfDetail['debuff']['freeze']) && $selfBattleAttr['stun'] >= rand(1, 1000)) {
                $this->triggerStun($log, $enemyBattleAttr, $selfSkill, $selfBattleAttr, $selfDetail, $enemyDetail);
            }
            //吸血
            AttributeComputeService::getInstance()->limitHpSub($enemyDetail, $hurt, $log);
            $this->triggerLifeSteal($selfBattleAttr, $hurt, $selfDetail, $log);
            $log['shanghaiData']['_val'] = $hurt;

            $this->computeEnemyHp($enemyDetail, $hurt);
            MagicSkillService::getInstance()->addStaminaToAttacked($enemyDetail, $hurt);
        } else {
            $isDodge = true;
            $log['shanghaiData']['type'][] = self::DODGE;
            $log['shanghaiData']['_val'] = 0;
            //每次闪避
            $log['isFirst'] = false;
            SkillService::getInstance()->triggerSkill($log, $enemySkill, $enemyBattleAttr, $selfBattleAttr,
                $enemyDetail, $selfDetail, 10);
            unset($log['isFirst']);
            SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfBattleAttr, $enemyBattleAttr, $selfDetail, $enemyDetail, 24);

        }

        //攻击时,触发技能
        SkillService::getInstance()->attackTriggerSkill($log, $selfSkill, $selfBattleAttr, $enemyBattleAttr, $selfDetail, $enemyDetail);
        //每次触发连击
        SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfBattleAttr, $enemyBattleAttr,
            $selfDetail, $enemyDetail, 3);

        //每次被攻击,触发技能
        $log['isFirst'] = false;
        SkillService::getInstance()->triggerSkill($log, $enemySkill, $enemyBattleAttr, $selfBattleAttr,
            $enemyDetail, $selfDetail, 11);
        unset($log['isFirst']);
        SkillService::getInstance()->attackedTriggerSkill($log, $enemySkill, $selfBattleAttr, $enemyBattleAttr, $selfDetail, $enemyDetail);


        if ($enemyDetail['hp'] <= 0 && $enemyRevive > 0 && (!isset($enemyDetail['debuff']['freeze']) || isset($enemySkill['magic'][133400]))) {
            $log['shanghaiData']['type'][] = self::REVIVE;
            $log['extShanghaiData'] = [];
        }

        AttributeComputeService::getInstance()->limitHpExtSub($selfDetail, $enemyDetail, $log);
        //处理血量
        if ($log['hurt']['self'] > 0 && $enemyDetail['hp'] > 0) {
            $this->computeEnemyHp($enemyDetail, $log['hurt']['self']);
            if ($enemyDetail['hp'] <= 0 && $enemyRevive > 0 && (!isset($enemyDetail['debuff']['freeze']) || isset($enemySkill['magic'][133400]))) {
                $log['extShanghaiData'][count($log['extShanghaiData']) - 1]['type'][] = self::REVIVE;
            }
            MagicSkillService::getInstance()->addStaminaToAttacked($enemyDetail, $log['hurt']['self']);
        }
        if ($log['hurt']['enemy'] > 0 && $selfDetail['hp'] > 0) {
            $this->computeEnemyHp($selfDetail, $log['hurt']['enemy']);
            if ($selfDetail['hp'] <= 0 && $selfRevive > 0 && (!isset($selfDetail['debuff']['freeze']) || isset($selfSkill['magic'][133400]))) {
                $log['extEnemyShanghaiData'][count($log['extEnemyShanghaiData']) - 1]['type'][] = self::REVIVE;
            }
            MagicSkillService::getInstance()->addStaminaToAttacked($selfDetail, $log['hurt']['enemy']);
        }
        unset($log['hurt']);

        $log['hp'] = ['self' => $selfDetail['hp'], 'enemy' => $enemyDetail['hp']];
        $log['stamina'] = ['self' => $selfDetail['stamina'], 'enemy' => $enemyDetail['stamina']];
        $battleLog[] = $log;
        $selfSkill = $selfSkillCopy;

        if ($enemyDetail['hp'] > 0 && $myMagicTriggerPet) {
            $log = $this->petAttack($tmp, $selfPet[0], 0, $selfSkill, $enemySkill, $selfRevive, $enemyRevive, $selfBattleAttr, $enemyBattleAttr, $selfDetail, $enemyDetail, $selfType22Skill);
            $battleLog[] = $log;
            $myMagicTriggerRound = $log['round'];
            $myMagicTriggerPet = false;//是否触发副将出手
            $myReviveTriggerPet = false;
        }


        if ($enemyDetail['hp'] > 0) {
            $isAttackBack = false;
            if ($enemyDetail['hp'] > 0) {
                if (!isset($enemyDetail['debuff']['stun']) && !isset($enemyDetail['debuff']['freeze']) && $enemyBattleAttr['attack_back'] >= rand(1, 1000)) {
                    //触发反击
                    $this->triggerEnemyAttackBack($selfBattleAttr['attack_back'],
                        floor($enemyBattleAttr['attack_back'] / 2), $selfDetail, $enemyDetail, $selfBattleAttr,
                        $enemyBattleAttr, $battleLog, $tmp, $selfSkill, $enemySkill, $selfPet, $enemyPet, $selfRevive,
                        $enemyRevive, $selfType22Skill, $enemyType22Skill, $myAttackTriggerPet, $enemyAttackTriggerPet);
                    $isAttackBack = true;
                }

                if (!$isDodge && !$isAttackBack && $selfDetail['hp'] > 0 && $enemyDetail['hp'] > 0 && !isset($selfDetail['debuff']['stun']) && !isset($selfDetail['debuff']['freeze']) && $doubleAttack >= rand(1, 1000)) {
                    //触发连击
                    $this->triggerDoubleAttack(floor($doubleAttack / 2), $selfDetail, $enemyDetail,
                        $enemyBattleAttr, $selfBattleAttr, $battleLog, $tmp, $selfSkill, $enemySkill, $selfPet,
                        $enemyPet, $selfRevive, $enemyRevive, $myReviveTriggerPet, $enemyAttackTriggerPet,
                        $myMagicTriggerPet, $myMagicTriggerRound);
                }
            }
        }

    }


    //结算地方血量
    public function computeEnemyHp(&$enemyAttr, int &$hurt): void
    {
        $hpCopy = $enemyAttr['hp'];
        $enemyAttr['hp'] = sub($enemyAttr['hp'], $hurt);
        if ($enemyAttr['hp'] < 0) {
            $enemyAttr['hp'] = '0';
            $hurt = $hpCopy;
        }
    }


    public function buffClearEnemy(&$selfLineup, int $roundCount): void
    {
        if (isset($selfLineup['buff_magic']) && $selfLineup['buff_magic']) {
            foreach ($selfLineup['buff_magic'] as $typeName => $skillList) {
                foreach ($skillList as $skillId => $effectDetail) {
                    $selfLineup['buff_magic'][$typeName][$skillId]['limit']--;
                    if ($selfLineup['buff_magic'][$typeName][$skillId]['limit'] <= 0) {
                        unset($selfLineup['buff_magic'][$typeName][$skillId]);
                    }
                }
            }
        }

        if ($selfLineup['buff'] || $selfLineup['debuff']) {
            $mainBuff = ['buff' => $selfLineup['buff'], 'debuff' => $selfLineup['debuff']];
            foreach ($mainBuff as $buffName => $buffDetail) {
                foreach ($buffDetail as $attrName => $effectDetail) {
                    if($attrName != 'freeze') continue; //冰凍獨立清理
                    $selfLineup[$buffName][$attrName]--;
                    if ($selfLineup[$buffName][$attrName] <= 0) unset($selfLineup[$buffName][$attrName]);
                }
            }
        }


    }

    public function buffClear(&$selfLineup, int $roundCount): void
    {

        if ($selfLineup['buff'] || $selfLineup['debuff']) {
            $mainBuff = ['buff' => $selfLineup['buff'], 'debuff' => $selfLineup['debuff']];
            foreach ($mainBuff as $buffName => $buffDetail) {
                foreach ($buffDetail as $attrName => $effectDetail) {
                    if($attrName == 'freeze') continue; //冰凍獨立清理
                    $selfLineup[$buffName][$attrName]--;
                    if ($selfLineup[$buffName][$attrName] <= 0) unset($selfLineup[$buffName][$attrName]);
                }
            }
        }

        //灵兽加成及减成
        if ($selfLineup['buff_pet'] || $selfLineup['debuff_pet']) {
            $buffList = ['buff_pet' => $selfLineup['buff_pet'], 'debuff_pet' => $selfLineup['debuff_pet']];
            foreach ($buffList as $buffName => $buffDetail) {
                foreach ($buffDetail as $attrName => $effectDetail) {
                    if ($effectDetail['round'] >= $roundCount) continue;
                    $selfLineup[$buffName][$attrName]['limit']--;
                    if ($selfLineup[$buffName][$attrName]['limit'] <= 0) unset($selfLineup[$buffName][$attrName]);
                }
            }
        }

        //技能buff加成及减成
        if (isset($selfLineup['buff_skill']) && $selfLineup['buff_skill']) {
            foreach ($selfLineup['buff_skill'] as $typeName => $skillList) {
                foreach ($skillList as $skillId => $effectDetail) {
                    $selfLineup['buff_skill'][$typeName][$skillId]['limit']--;
                    if ($selfLineup['buff_skill'][$typeName][$skillId]['limit'] <= 0) {
                        unset($selfLineup['buff_skill'][$typeName][$skillId]);
                    }
                }
            }
        }

//        //神通造成的技能状态
//        if (isset($selfLineup['buff_magic']) && $selfLineup['buff_magic']) {
//            foreach ($selfLineup['buff_magic'] as $typeName => $skillList) {
//                if($typeName == 'freeze') continue; //冰凍獨立清理
//                foreach ($skillList as $skillId => $effectDetail) {
//                    $selfLineup['buff_magic'][$typeName][$skillId]['limit']--;
//                    if ($selfLineup['buff_magic'][$typeName][$skillId]['limit'] <= 0) {
//                        unset($selfLineup['buff_magic'][$typeName][$skillId]);
//                    }
//                }
//            }
//        }

        //特殊技能效果
        if (isset($selfLineup['buff_skill_special']) && $selfLineup['buff_skill_special']) {
            foreach ($selfLineup['buff_skill_special'] as $skillId => $effectDetail) {
                $selfLineup['buff_skill_special'][$skillId]['limit']--;
                if ($selfLineup['buff_skill_special'][$skillId]['limit'] <= 0) {
                    unset($selfLineup['buff_skill_special'][$skillId]);
                }
            }
        }

    }


    //加血
    public function getLifeStealNumber(&$lifeStealAdd, &$selfDetail): void
    {
        if ($selfDetail['hp'] == 0) {
            $lifeStealAdd = 0;
            return;
        }

        $totalHp = add($selfDetail['hp'], $lifeStealAdd);
        if ($totalHp >= $selfDetail['hp_max']) {
            $lifeStealAdd = sub($selfDetail['hp_max'], $selfDetail['hp']);
            $selfDetail['hp'] = $selfDetail['hp_max'];
        } else {
            $selfDetail['hp'] = $totalHp;
        }

    }

    public function getBattlePetEffectAttr($selfAttr, int $roundCount): array
    {
        $original = $selfAttr;
        unset($original['buff'], $original['debuff'], $original['buff_pet'], $original['debuff_pet']);

        //buff_skill   type=>Consts::BUFF_TYPE_LIST
//        $selfAttr['buff_skill'][$type][$skillId] = ['limit' => $rount, 'count' => $total,
//            'val' => $val];
        //var_dump('aaa:' . $original['double_attack']);
//        var_dump($roundCount);
        if (isset($selfAttr['buff_skill']) && $selfAttr['buff_skill']) {
            foreach ($selfAttr['buff_skill'] as $typeName => $skillList) {
                foreach ($skillList as $skillId => $effectDetail) {
                    //这两个技能buff都是下回合生效
                    if (($skillId == 131201 || $skillId == 131203) && $roundCount == $effectDetail['round']) {
                        continue;
                    }


//                    if ($skillId == 131101) {
//                        var_dump('aaa:' . $original['attack']);
//                    }
                    $val = $effectDetail['count'] * $effectDetail['val'];
                    //基础属性百分比加成
                    if (strpos($typeName, 'ratio_') === 0) {
                        $typeName = substr($typeName, 6);
                        $val = mul($original[$typeName], $val / 1000);
                    }

                    $original[$typeName] += $val;
//                    if ($skillId == 131101) {
//                        var_dump('round:' . $roundCount);
//                        var_dump($skillList[131101]);
//                        var_dump('bbb:' . $original['attack']);
//                    }
                }

            }
        }
//        var_dump($selfAttr['fortify_pet']);
        //var_dump('bbb:' . $original['double_attack']);
        //无灵兽加成及减成
        if (!$selfAttr['buff_pet'] && !$selfAttr['debuff_pet']) return $original;

        $buffList = ['buff_pet' => $selfAttr['buff_pet'], 'debuff_pet' => $selfAttr['debuff_pet']];
        foreach ($buffList as $buffName => $buffDetail) {
            foreach ($buffDetail as $attrName => $effectDetail) {
                //当前回合生效

                $val = $effectDetail['count'] * $effectDetail['val'];
                //累计加成
                if (strpos($attrName, 'sum_') === 0) $attrName = substr($attrName, 4);
                //基础属性百分比加成
                if (strpos($attrName, 'ratio_') === 0) {
                    $attrName = substr($attrName, 6);
                    $val = mul($original[$attrName], $effectDetail['val'] / 1000);
                }

                $original[$attrName] += $val;
            }
        }


        return $original;
    }

    //副将攻击
    public function petAttack($tmp, $petValue, $id, $selfSkill, $enemySkill, $selfRevive, $enemyRevive, &$selfBattleAttr, &$enemyBattleAttr, &$selfDetail, &$enemyDetail, &$selfType22Skill, $isTriggerPet = false)
    {
        //计算灵兽buff及debuff+技能buff
        $selfPetBattleAttr = BattleService::getInstance()->getBattlePetEffectAttr($selfDetail, $tmp['round']);
        $enemyPetBattleAttr = BattleService::getInstance()->getBattlePetEffectAttr($enemyDetail, $tmp['round']);

        //属性可能存在buff影响，每次重新计算
        $selfBattleAttr = AttributeComputeService::getInstance()->getBattleAttr($selfPetBattleAttr, $enemyPetBattleAttr);
        $enemyBattleAttr = AttributeComputeService::getInstance()->getBattleAttr($enemyPetBattleAttr, $selfPetBattleAttr);


        list($subHp, $log) = SkillService::getInstance()->petAttackSkill($selfBattleAttr,
            $enemyBattleAttr, $selfDetail, $enemyDetail, $tmp, $petValue,
            $id, $selfSkill);

        $index = 0;
        if ($isTriggerPet) {
            foreach ($enemySkill['spirit'] as $key => $value) {
                if ($isTriggerPet == $key) {
                    $log['spirit'][] = $index;
                }
                $index++;
            }
            unset($index);
        }

        if ($subHp > 0) {
            AttributeComputeService::getInstance()->limitHpSub($enemyDetail, $subHp, $log);
            $this->computeEnemyHp($enemyDetail, $subHp);
            if ($enemyDetail['hp'] <= 0 && $enemyRevive > 0 && (!isset($enemyDetail['debuff']['freeze']) || isset($enemySkill['magic'][133400]))) {
                $log['shanghaiData']['type'][] = self::REVIVE;
            }
            MagicSkillService::getInstance()->addStaminaToAttacked($enemyDetail, $subHp);
        }


        $log['hurt'] = ['self' => 0, 'enemy' => 0];
        if (!in_array($petValue['id'], SkillService::PET_NO_ATTASK_SKILL_LIST)) {
            SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfBattleAttr,
                $enemyBattleAttr, $selfDetail, $enemyDetail, 21);
            if (MagicSkillService::getInstance()->isNegativeStatus($enemyDetail)) {
                SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfBattleAttr,
                    $enemyBattleAttr, $selfDetail, $enemyDetail, 20);
            }
        }
        SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfBattleAttr, $enemyBattleAttr, $selfDetail, $enemyDetail, 7);
        if ($selfType22Skill) {
            $skillList = SkillService::getInstance()->formatSkillList($selfType22Skill);
            SkillService::getInstance()->triggerSkill($log, $skillList, $selfBattleAttr,
                $enemyBattleAttr, $selfDetail, $enemyDetail, 22);
            $selfType22Skill = [];
        }
        //被攻击时
        SkillService::getInstance()->attackedTriggerSkill($log, $enemySkill, $selfBattleAttr, $enemyBattleAttr, $selfDetail, $enemyDetail);


        AttributeComputeService::getInstance()->limitHpExtSub($selfDetail, $enemyDetail, $log);
        //处理血量
        if ($log['hurt']['self'] > 0 && $enemyDetail['hp'] > 0) {
            $this->computeEnemyHp($enemyDetail, $log['hurt']['self']);
            if ($enemyDetail['hp'] <= 0 && $enemyRevive > 0 && (!isset($enemyDetail['debuff']['freeze']) || isset($enemySkill['magic'][133400]))) {
                $log['extShanghaiData'][count($log['extShanghaiData']) - 1]['type'][] = self::REVIVE;
            }
            MagicSkillService::getInstance()->addStaminaToAttacked($enemyDetail, $log['hurt']['self']);
        }


        if ($log['hurt']['enemy'] > 0 && $selfDetail['hp'] > 0) {
            $this->computeEnemyHp($selfDetail, $log['hurt']['enemy']);
            if ($selfDetail['hp'] <= 0 && $selfRevive > 0 && (!isset($selfDetail['debuff']['freeze']) || isset($selfSkill['magic'][133400]))) {
                $log['extEnemyShanghaiData'][count($log['extEnemyShanghaiData']) - 1]['type'][] = self::REVIVE;
            }
            MagicSkillService::getInstance()->addStaminaToAttacked($selfDetail, $log['hurt']['enemy']);
        }


        unset($log['hurt']);


        $log['stamina'] = ['self' => $selfDetail['stamina'], 'enemy' => $enemyDetail['stamina']];
        $log['hp'] = ['self' => $selfDetail['hp'], 'enemy' => $enemyDetail['hp']];

        return $log;
    }


    //道法连击
    public function triggerMagicDoubleAttack(&$battleLog, $tmp, &$selfDetail, &$enemyDetail, &$selfBattleAttr, &$enemyBattleAttr, $selfSkill, $enemySkill, $selfRevive, $enemyRevive)
    {
        //计算灵兽buff及debuff+技能buff
        $selfPetBattleAttr = BattleService::getInstance()->getBattlePetEffectAttr($selfDetail, $tmp['round']);
        $enemyPetBattleAttr = BattleService::getInstance()->getBattlePetEffectAttr($enemyDetail, $tmp['round']);

        //属性可能存在buff影响，每次重新计算
        $selfBattleAttr = AttributeComputeService::getInstance()->getBattleAttr($selfPetBattleAttr, $enemyPetBattleAttr);
        $enemyBattleAttr = AttributeComputeService::getInstance()->getBattleAttr($enemyPetBattleAttr, $selfPetBattleAttr);


        //反击道法连击
        $battleLog[count($battleLog) - 1]['shanghaiData']['type'][] = self::MAGIC_DOUBLE_ATTACK;//下一条为道法连击


        //触发后仅造成道法一定的伤害和治疗，无法触发特殊效果（比如冰封效果、灼烧效果），每次释放道法仅会触发一次道法连击。
        $log = $tmp;
        $log['hurt'] = ['self' => 0, 'enemy' => 0];
        $log['shanghaiData'] = ['type' => [self::TRIGGER_MAGIC_DOUBLE_ATTACK], '_val' => '0'];//下一条为触发道法连击
        //计算道法伤害
        $hurt = 0;
        $skillData = ['id' => $selfDetail['magicInitiativeSkill'], 'lv' => $selfSkill['magic'][$selfDetail['magicInitiativeSkill']]];
        //触发道法主动技能
        MagicSkillService::getInstance()->triggerSkill($log, $skillData, $selfBattleAttr, $enemyBattleAttr,
            $selfDetail, $enemyDetail, $hurt, $selfSkill, $myMagicTriggerPet);
        $log['magic'][] = 0;
        $log['shanghaiData']['type'][] = self::MAGIC_ATTACK;

        if ($hurt > 0) {
            $log['shanghaiData']['_val'] = add($hurt, $log['shanghaiData']['_val']);
            //暴击
            if ($selfBattleAttr['critical_hit'] >= rand(1, 1000)) {
                $this->triggerCriticalHit($hurt, $log, $selfBattleAttr['fortify_critical_hit'],
                    $selfSkill, $selfBattleAttr, $enemyBattleAttr, $selfDetail, $enemyDetail);
            }
            //是否击晕对方
            if ($selfBattleAttr['stun'] >= rand(1, 1000)) {
                $this->triggerStun($log, $enemyBattleAttr, $selfSkill, $selfBattleAttr, $selfDetail, $enemyDetail);
            }
            //吸血
            AttributeComputeService::getInstance()->limitHpSub($enemyDetail, $hurt, $log);
            $this->triggerLifeSteal($selfBattleAttr, $hurt, $selfDetail, $log);
            //计算伤害,扣除血量
            $this->computeEnemyHp($enemyDetail, $hurt);
        }


        if ($hurt > 0) {
            MagicSkillService::getInstance()->addStaminaToAttacked($enemyDetail, $hurt);
        }

        //释放兵法时
        SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfBattleAttr, $enemyBattleAttr,
            $selfDetail, $enemyDetail, 19);


        //攻击时,触发技能
        SkillService::getInstance()->attackTriggerSkill($log, $selfSkill, $selfBattleAttr, $enemyBattleAttr,
            $selfDetail, $enemyDetail);

        //每次被攻击,触发技能
        $log['isFirst'] = false;
        SkillService::getInstance()->triggerSkill($log, $enemySkill, $enemyBattleAttr, $selfBattleAttr,
            $enemyDetail, $selfDetail, 11);
        unset($log['isFirst']);
        SkillService::getInstance()->attackedTriggerSkill($log, $enemySkill, $selfBattleAttr, $enemyBattleAttr,
            $selfDetail, $enemyDetail);

        if ($enemyDetail['hp'] <= 0 && $enemyRevive > 0 && (!isset($enemyDetail['debuff']['freeze']) || isset($enemySkill['magic'][133400]))) {
            $log['shanghaiData']['type'][] = self::REVIVE;
            $log['extShanghaiData'] = [];
        }
        AttributeComputeService::getInstance()->limitHpExtSub($selfDetail, $enemyDetail, $log);

        //处理血量
        if ($log['hurt']['self'] > 0 && $enemyDetail['hp'] > 0) {
            $this->computeEnemyHp($enemyDetail, $log['hurt']['self']);
            if ($enemyDetail['hp'] <= 0 && $enemyRevive > 0 && (!isset($enemyDetail['debuff']['freeze']) || isset($enemySkill['magic'][133400]))) {
                $log['extShanghaiData'][count($log['extShanghaiData']) - 1]['type'][] = self::REVIVE;
            }
            MagicSkillService::getInstance()->addStaminaToAttacked($enemyDetail, $log['hurt']['self']);
        }
        if ($log['hurt']['enemy'] > 0 && $selfDetail['hp'] > 0) {
            $this->computeEnemyHp($selfDetail, $log['hurt']['enemy']);
            if ($selfDetail['hp'] <= 0 && $selfRevive > 0 && (!isset($selfDetail['debuff']['freeze']) || isset($selfSkill['magic'][133400]))) {
                $log['extEnemyShanghaiData'][count($log['extEnemyShanghaiData']) - 1]['type'][] = self::REVIVE;
            }
            MagicSkillService::getInstance()->addStaminaToAttacked($selfDetail, $log['hurt']['enemy']);
        }
        unset($log['hurt']);


        $log['hp'] = ['self' => $selfDetail['hp'], 'enemy' => $enemyDetail['hp']];
        $log['stamina'] = ['self' => $selfDetail['stamina'], 'enemy' => $enemyDetail['stamina']];
        $battleLog[] = $log;

    }


}