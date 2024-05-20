<?php

namespace App\Api\Service\Module;


use App\Api\Service\AttributeComputeService;
use App\Api\Service\BattleService;
use App\Api\Service\SkillService;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigSkill;
use App\Api\Utils\Consts;
use EasySwoole\Component\CoroutineSingleTon;

class MagicSkillService
{
    use CoroutineSingleTon;

    private array $staminaConfig;

    public function __construct()
    {
        //0|10000|2000|20   MAGIC_BATTLE_PARAM
        $config = ConfigParam::getInstance()->getFmtParam('MAGIC_BATTLE_PARAM');
        $this->staminaConfig = ['stamina_start' => $config[0], 'stamina_max' => $config[1],
            'attask_add' => $config[2], 'hurt_add' => $config[3]];
    }


    public function init(&$selfDetail)
    {
        $selfDetail['stamina'] = $this->staminaConfig['stamina_start'];
    }

    /**
     * 是否已经到达最大值
     * @param $selfDetail
     * @return bool
     */
    public function isStaminaMax($selfDetail)
    {
        if ($selfDetail['stamina'] >= $this->staminaConfig['stamina_max']) {
            return true;
        }
        return false;
    }


    /**
     * 攻击时新增
     * @param $selfDetail
     * @return int
     */
    public function addStaminaToAttask(&$selfDetail)
    {
        $value = 0;
        if ($selfDetail['magicInitiativeSkill']) {
            $stamina = add($selfDetail['stamina'], $this->staminaConfig['attask_add']);
            $value = $this->staminaConfig['attask_add'];
            if ($stamina > $this->staminaConfig['stamina_max']) {
                $value = sub($this->staminaConfig['stamina_max'], $selfDetail['stamina']);
                $selfDetail['stamina'] = $this->staminaConfig['stamina_max'];
            } else {
                $selfDetail['stamina'] = $stamina;
            }
        }
        //return $value;
    }

    /**
     * 受到攻击时
     * @param $selfDetail
     * @param $hurt
     * @return void
     */
    public function addStaminaToAttacked(&$selfDetail, $hurt)
    {
        if (isset($selfDetail['magicInitiativeSkill']) && $selfDetail['magicInitiativeSkill']) {
            //受到攻击时，将扣除的总血量的百分比除以2在乘以10000，就是这次获得的妖气值，不管谁打的都算。
            $value = ($hurt / $selfDetail['hp_max'] / ($this->staminaConfig['hurt_add'] / 10)) * 10000;
            $value = (int)$value;
            $selfDetail['stamina'] = add($selfDetail['stamina'], $value);
            if ($selfDetail['stamina'] > $this->staminaConfig['stamina_max']) {
                $selfDetail['stamina'] = $this->staminaConfig['stamina_max'];
            }
        }
    }

    /**
     * 扣除妖气
     * @param $enemyDetail
     * @param $num
     * @return void
     */
    public function subStamina(&$enemyDetail, $num)
    {
        if ($enemyDetail['magicInitiativeSkill']) {
            $enemyDetail['stamina'] = $enemyDetail['stamina'] - $num;
            if ($enemyDetail['stamina'] < $this->staminaConfig['stamina_start']) {
                $enemyDetail['stamina'] = $this->staminaConfig['stamina_start'];
            }
        }
    }

    /**
     * 增加妖气
     * @param $selfDetail
     * @param $num
     * @return void
     */
    public function addStamina(&$selfDetail, $num)
    {
        if ($selfDetail['magicInitiativeSkill']) {
            $selfDetail['stamina'] = add($selfDetail['stamina'], $num);
            if ($selfDetail['stamina'] > $this->staminaConfig['stamina_max']) {
                $selfDetail['stamina'] = $this->staminaConfig['stamina_max'];
            }
        }
    }


    //判断是否负面状态
    public function isNegativeStatus($enemyDetail)
    {
        if (isset($enemyDetail['stun'])) return true;

        $statusList = ['freeze', 'burn',];

        foreach ($statusList as $type) {
            if (isset($enemyDetail[$type]) && count($enemyDetail[$type]) > 1) {
                return true;
            }
        }
        return false;
    }


    public function addMagicStatus($type, $id, $round, $value, &$enemyDetail, &$selfDetail, &$log, $extValue = 0)
    {
        //判断是否免疫
        if (in_array($type, ['freeze', 'burn']) && isset($enemyDetail['buff']['immunity']) && $enemyDetail['buff']['immunity'] >= 1) return;
        $statusData = [];
        if ($type != 'cure') {
            $statusData = ['id' => $id, 'type' => Consts::BATTLE_BUFF_STATUS[$type], 'round' => $round + 1];
        }
        $enemyDetailCopy = $enemyDetail;
        if ($type == 'immunity') {
            $enemyDetailCopy = $selfDetail;
        }

        if ($type == 'freeze') {
            if (isset($enemyDetail['debuff'][$type])) {
                $enemyDetail['debuff'][$type] = $enemyDetail['debuff'][$type] + $round;
            } else {
                $enemyDetail['debuff'][$type] = $round;
                $statusData['round'] = $round;
            }
        } elseif ($type == 'cure') {
            $statusData = [];
            $selfDetail['buff_magic'][$type][$id] = ['limit' => $round + 1, 'value' => $value];
        } elseif ($type == 'immunity') {
            if (isset($selfDetail['buff'][$type])) {
                $selfDetail['buff'][$type] = $selfDetail['buff'][$type] + $round;
            } else {
                $selfDetail['buff'][$type] = $round;
            }
        } else {
            $enemyDetail['buff_magic'][$type][$id] = ['limit' => $round + 1, 'value' => $value, 'ext_value' => $extValue];
        }

        if ($statusData) {
            $limit = 0;

            if (isset($enemyDetailCopy['buff_magic'][$type])) {
                foreach ($enemyDetailCopy['buff_magic'][$type] as $data) {
                    if ($data['limit'] > $limit) {
                        $limit = $data['limit'];
                    }
                }
            }
            $limit = $limit - 1;
            if ($limit > $statusData['round']) {
                $statusData['round'] = $limit;
            }
            $log['status'][] = $statusData;
        }

    }


    public function triggerSkill(&$log, $skillData, $selfBattleAttr, $enemyBattleAttr, &$selfDetail, &$enemyDetail, &$hurt, $selfSkill, &$myMagicTriggerPet)
    {

        //获取技能参数
        $skillConf = ConfigSkill::getInstance()->getOne($skillData['id']);
        $skillParam = [
            'a' => $skillConf['params'][0][0] + ($skillData['lv'] - 1) * $skillConf['upgradeParams'][0][0],
            'b' => $skillConf['params'][0][1] + ($skillData['lv'] - 1) * $skillConf['upgradeParams'][0][1],
            'c' => $skillConf['params'][0][2] + ($skillData['lv'] - 1) * $skillConf['upgradeParams'][0][2],
            'd' => $skillConf['params'][0][3] + ($skillData['lv'] - 1) * $skillConf['upgradeParams'][0][3],
        ];


        $hurt = AttributeComputeService::getInstance()->computeMagicHit($selfBattleAttr['attack'],
            $enemyBattleAttr['defence'], $selfBattleAttr['final_hurt'], $selfBattleAttr['fortify_magic'],
            $skillParam['a']);
        //使用完消耗所有妖力
        $this->init($selfDetail);//先消耗所有，避免后面加妖力

        switch ($skillData['id']) {
            case 131100://恢复攻击力%a生命值，并提升%b防御，持续$c回合。
                $hurt = 0;
                $value = div($selfBattleAttr['attack'] * $skillParam['a'], 1000);
                AttributeComputeService::getInstance()->computeSkillCure($value, $selfBattleAttr['fortify_cure']);
                BattleService::getInstance()->getLifeStealNumber($value, $selfDetail);
                if ($value > 0) {
                    $log['extBuffdata'][] = ['type' => 2, '_val' => $value];
                }
                SkillService::getInstance()->addBuff($skillParam['c'], 'defence', $skillData['id'], $skillParam['b'], $log, $selfDetail, 'mybuff');
                break;
            case 131101://进行一次%a攻击力的打击，并提升%b攻击，持续$c回合。
                SkillService::getInstance()->addBuff($skillParam['c'], 'attack', $skillData['id'], $skillParam['b'], $log, $selfDetail, 'mybuff');
                break;
            case 131102://进行一次%a攻击力的打击，有%b概率击晕敌人$c回合。
                if ($skillParam['b'] >= rand(1, 1000)) {
                    $item = $skillParam['c'] + 1;
                    if (isset($enemyDetail['debuff']['stun'])) {
                        if ($enemyDetail['debuff']['stun'] > $item) {
                            $item = $enemyDetail['debuff']['stun'];
                        }
                    }
                    $enemyDetail['debuff']['stun'] = $item;
                    $log['shanghaiData']['type'][] = Consts::STUN;
                    $specialBuff = ['type' => Consts::STUN, 'num' => $item];
                    $log['shanghaiData']['specialBuff'][] = $specialBuff;
                    SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfBattleAttr, $enemyBattleAttr, $selfDetail, $enemyDetail, 5);
                }
                break;
            case 131200://进行一次%a攻击力的打击，并减少敌人$b点士气。
                $this->subStamina($enemyDetail, $skillParam['b']);
                break;
            case 131201://进行一次%a攻击力的打击，并提升下回合%b连击。
                SkillService::getInstance()->addBuff(2, 'double_attack', $skillData['id'], $skillParam['b'], $log, $selfDetail, 'mybuff');
                break;
            case 131202://进行一次%a攻击力的打击，并提升%b强化暴伤，持续$c回合。
                SkillService::getInstance()->addBuff($skillParam['c'], 'fortify_critical_hit', $skillData['id'], $skillParam['b'], $log, $selfDetail, 'mybuff');
                break;
            case 131203://进行一次%a攻击力的打击，并提升下回合%b闪避。
                SkillService::getInstance()->addBuff(2, 'dodge', $skillData['id'], $skillParam['b'], $log, $selfDetail, 'mybuff');
                break;
            case 131300://进行一次%a攻击力的打击，若敌人当前生命值低于%c，本次打击提升至%b攻击力。
                if ($enemyDetail['hp'] < div($enemyDetail['hp'] * $skillParam['c'], 1000)) {
                    $hurt = AttributeComputeService::getInstance()->computeMagicHit($selfBattleAttr['attack'],
                        $enemyBattleAttr['defence'], $selfBattleAttr['final_hurt'], $selfBattleAttr['fortify_magic'],
                        $skillParam['c']);
                }
                break;
            case 131301://恢复攻击力%a生命值，并提升%b最终减伤，持续到下回合结束。
                $hurt = 0;
                $value = div($selfBattleAttr['attack'] * $skillParam['a'], 1000);
                AttributeComputeService::getInstance()->computeSkillCure($value, $selfBattleAttr['fortify_cure']);
                BattleService::getInstance()->getLifeStealNumber($value, $selfDetail);
                if ($value > 0) {
                    $log['extBuffdata'][] = ['type' => 2, '_val' => $value];
                }
                SkillService::getInstance()->addBuff(2, 'final_sub_hurt', $skillData['id'], $skillParam['b'], $log, $selfDetail, 'mybuff');
                break;
            case 131302://进行一次%a攻击力的打击，并恢复攻击力%b生命值。
                $value = div($selfBattleAttr['attack'] * $skillParam['b'], 1000);
                AttributeComputeService::getInstance()->computeSkillCure($value, $selfBattleAttr['fortify_cure']);
                BattleService::getInstance()->getLifeStealNumber($value, $selfDetail);
                if ($value > 0) {
                    $log['extBuffdata'][] = ['type' => 2, '_val' => $value];
                }
                break;
            case 131303://进行一次%a攻击力的打击，并使敌人燃烧，每回合造成攻击力%b伤害，持续$c回合。（燃烧伤害无视最终增伤和最终减伤效果）
            case 131402://进行一次%a攻击力的打击，并使敌人燃烧，每回合造成攻击力%b伤害，持续$c回合。（燃烧伤害无视最终增伤和最终减伤效果）
                $this->addMagicStatus('burn', $skillData['id'], $skillParam['c'], $skillParam['b'], $enemyDetail, $selfDetail, $log);
                break;
            case 131304://恢复%a已损生命值，并提升%b强化治疗，持续$c回合。
                $value = div(($selfDetail['hp_max'] - $selfDetail['hp']) * $skillParam['a'], 1000);
                AttributeComputeService::getInstance()->computeSkillCure($value, $selfBattleAttr['fortify_cure']);
                BattleService::getInstance()->getLifeStealNumber($value, $selfDetail);
                if ($value > 0) {
                    $log['extBuffdata'][] = ['type' => 2, '_val' => $value];
                }
                SkillService::getInstance()->addBuff($skillParam['c'], 'fortify_cure', $skillData['id'], $skillParam['b'], $log, $selfDetail, 'mybuff');
                $hurt = 0;
                break;
            case 131400://恢复攻击力%a生命值，后续每回合恢复攻击力%b生命，持续$c回合。
                $hurt = 0;
                $value = div($selfBattleAttr['attack'] * $skillParam['a'], 1000);
                AttributeComputeService::getInstance()->computeSkillCure($value, $selfBattleAttr['fortify_cure']);
                BattleService::getInstance()->getLifeStealNumber($value, $selfDetail);
                if ($value > 0) {
                    $log['extBuffdata'][] = ['type' => 2, '_val' => $value];
                }
                $this->addMagicStatus('cure', $skillData['id'], $skillParam['c'], $skillParam['b'], $enemyDetail, $selfDetail, $log);
                break;
            case 131401://进行一次%a攻击力的打击，并使敌人冰冻至下回合。
                $this->addMagicStatus('freeze', $skillData['id'], 1, 0, $enemyDetail, $selfDetail, $log);
                break;
            case 131403://进行一次%a攻击力的打击，并提升%b暴击和%c强化暴伤，持续$d回合。
                SkillService::getInstance()->addBuff($skillParam['d'], 'critical_hit', $skillData['id'], $skillParam['b'], $log, $selfDetail, 'mybuff');
                SkillService::getInstance()->addBuff($skillParam['d'], 'fortify_critical_hit', $skillData['id'], $skillParam['c'], $log, $selfDetail, 'mybuff');
                break;
            case 131404://进行一次%a攻击力的打击，并提升$b点士气。
                $this->addStamina($selfDetail, $skillParam['b']);
                break;
            case 131405://进行一次%a攻击力的打击，有%b概率命令副将立即释放一次战技，释放后副将战技进入冷却时间。   释放副将技能。然后副将回合数重置
                if ($skillParam['b'] >= rand(1, 1000)) {
                    $myMagicTriggerPet = $skillData['id'];
                }
                break;
            case 131406://进行一次%a攻击力的打击，并降低敌人%b最终减伤，持续$c回合。
                $log['mybuff'][] = ['id' => $skillData['id'], "type" => Consts::BUFF_TYPE_LIST['defence_sub'], "heiheval" => $skillParam['c'], "ceng" => 0];
                $enemyDetail['buff_skill']['final_sub_hurt'][$skillData['id']] = ['limit' => $skillParam['c'], 'count' => 1, 'val' => -$skillParam['b']];
                break;
            default:
                break;
        }

    }


    public function triggerBurnStatus(&$log, $value, $enemyBattleAttr, $nameList)
    {
        $num = div($enemyBattleAttr['attack'] * $value, 1000);
        $log[$nameList['extShanghaiData']][] = ['type' => [], '_val' => $num];
        $log['hurt'][$nameList['self']] = add($log['hurt'][$nameList['self']], $num);
    }


    //触战技造成的状态
    public function triggerMagicStatus(&$log, &$selfDetail, &$enemyDetail, $selfBattleAttr, $enemyBattleAttr)
    {

        if (!isset($log['isFirst']) || $log['isFirst']) {
            $nameList = [
                'extShanghaiData' => 'extEnemyShanghaiData',//额外伤害
                'extBuffdata' => 'extBuffdata',//额外加血
                'self' => 'enemy',
            ];
        } else {
            $nameList = [
                'extShanghaiData' => 'extShanghaiData',//额外伤害
                'extBuffdata' => 'extEnemyBuffdata',//额外加血
                'self' => 'self',
            ];
        }


        foreach ($selfDetail['buff_magic'] as $type => $buffData) {
            $value = 0;
            $id = 0;
            foreach ($buffData as $skillId => $data) {
                if ($data['value'] > $value) {
                    $value = $data['value'];
                    $id = $skillId;
                }
            }
            if ($value <= 0) continue;
            switch ($type) {
                case 'burn':
                    //角色攻击力%a 伤害*（100%+增伤）
                    $this->triggerBurnStatus($log, $value, $enemyBattleAttr, $nameList);
                    break;
                case 'bleed'://每回合造成敌人最大生命值%a伤害，最高不超过自身%b攻击
                    //敌人最大生命值%a伤害 伤害*（100%）
                    $num = div($selfDetail['hp_max'] * $value, 1000);
                    $myAttack = div($enemyBattleAttr['attack'] * $buffData[$id]['ext_value'], 1000);
                    if ($num > $myAttack) {
                        $num = $myAttack;
                    }
                    $log[$nameList['extShanghaiData']][] = ['type' => [], '_val' => $num];
                    $log['hurt'][$nameList['self']] = add($log['hurt'][$nameList['self']], $num);
                    break;
                case 'cure':
                    //恢复攻击力%b生命 数值*（100%+强疗）
                    $num = div($selfBattleAttr['attack'] * $value * (1000 + $selfBattleAttr['fortify_cure']), 1000 * 1000);
                    BattleService::getInstance()->getLifeStealNumber($num, $selfDetail);
                    if ($num > 0) {
                        $log[$nameList['extBuffdata']][] = ['type' => 2, '_val' => $num];
                    }
                    break;
                default:
                    break;
            }

        }

    }

    public function triggerSpecialMagicStatus(&$log, &$selfDetail, &$enemyDetail, $selfBattleAttr, $enemyBattleAttr, $type, &$hurt, &$selfSkill)
    {
        //vulnerability
        if (!isset($enemyDetail['buff_magic'][$type]) || count($enemyDetail['buff_magic'][$type]) <= 0) {
            return;
        }


        switch ($type) {
            case 'vulnerability':
                //易伤状态，产生暴击后，附加额外%a伤害，并移除该状态。
                $value = 0;
                $id = 0;
                foreach ($enemyDetail['buff_magic'][$type] as $skillId => $item) {
                    if ($item['value'] > $value) {
                        $value = $item['value'];
                        $id = $skillId;
                    }
                }
                unset($enemyDetail['buff_magic'][$type]);
                $hurt = div($hurt * (1000 + $value), 1000);
                $log['shanghaiData']['_val'] = $hurt;
                //并移除该状态
                $statusData = ['id' => $id, 'type' => Consts::BATTLE_BUFF_STATUS[$type], 'round' => 0];
                $log['enemyStatus'] = $statusData;
                unset($selfSkill['magic'][132303]);
                break;
            default:
                break;
        }

    }

}
