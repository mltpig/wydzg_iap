<?php

namespace App\Api\Service;

use App\Api\Service\Module\MagicService;
use App\Api\Service\Module\MagicSkillService;
use App\Api\Service\Module\PetService;
use App\Api\Service\Module\SpiritService;
use EasySwoole\Component\CoroutineSingleTon;

class MonsterInvadeBattleService
{
    use CoroutineSingleTon;

    //异兽入侵只有普工 及抗性
    public function run(array $self, array $enemys, &$selfShowData, int $roundLimit): array
    {
        $selfLineup = $this->getBattleLineup($self);
        $enemyLineup = $this->getBattleLineup($enemys);
        $selfCount = count($selfLineup['list']);
        $enemyCount = count($enemyLineup['list']);
        $isContinue = true;
        $isSuccess = false;
        $roundCount = 1;
        $selfShowData['hp'] = $selfLineup['list'][$selfLineup['battle']]['hp'];
        $battleLog = [];
        $selfTotalHurt = $enemyTotalHurt = $rewardCount = '0';

        //副将
        $selfPetInfo = $selfLineup['list'][$selfLineup['battle']]['pet'];
        $selfPet = array();
        $enemyPet = array();
        $selfTactical = array();
        $selfSpirit = array();
        if ($selfPetInfo) {
            if ($selfPetInfo['active'] != -1) {
                $petId = $selfPetInfo['bag'][$selfPetInfo['active']]['id'];
                $petLv = $selfPetInfo['bag'][$selfPetInfo['active']]['lv'];
                $selfPet[0] = PetService::getInstance()->getPetActiveSkillAttr($petId, $petLv);
                $selfShowData['pet'] = [$selfPetInfo['bag'][$selfPetInfo['active']]['id']];
            }
        }

        //获取用户阵法数据
        if (isset($self[1]['tactical']) && $self[1]['tactical']) {
            $selfTactical = TacticalService::getInstance()->getTacticalSkill($self[1]['tactical']);
        }

        foreach ($selfTactical as $key => $lv) {
            $selfShowData['tactical'][] = ['id' => $key, 'lv' => $lv];
        }

        //获取用户精怪数据
        $selfSpiritList = array();
        if (isset($self[1]['spirit']) && $self[1]['spirit']) {
            $selfSpiritList = SpiritService::getInstance()->getSpiritList($self[1]['spirit']);
        }

        foreach ($selfSpiritList as $key => $lv) {
            list($id, $value) = SpiritService::getInstance()->getSpiritSkill($key, $lv);
            $selfSpirit[$id] = $value;
            $selfShowData['spirit'][] = ['id' => $key, 'lv' => $value];
        }


        //获取用户神通
        $selfMagic = array('pet' => 0);
        $selfShowData['magicInitiative'] = -1;
        $selfShowData['magic'] = array();
        $selfLineup['list'][$selfLineup['battle']]['magicInitiativeSkill'] = false;
        if (isset($self[1]['magic']) && $self[1]['magic']) {
            $magicList = MagicService::getInstance()->getMagicList($self[1]['magic']);
            foreach ($magicList as $key => $value) {
                if ($value['id'] == 0) {
                    continue;
                }
                if ($key == 1) {
                    $selfLineup['list'][$selfLineup['battle']]['magicInitiativeSkill'] = $value['skill_id'];
                    $selfShowData['magicInitiative'] = $value['id'];
                } elseif ($key == 2) {
                    $selfMagic['pet'] = $value['skill_id'];
                }
                $selfShowData['magic'][] = ['id' => (int)$value['skill_id'], 'lv' => (int)$value['lv']];
                $selfMagic[$value['skill_id']] = $value['lv'];
            }
        }


        $selfSkill = ['tactical' => $selfTactical, 'spirit' => $selfSpirit, 'magic' => $selfMagic];

        foreach ($enemyLineup['list'] as $key => $list) {
            $enemyLineup['list'][$key]['magicInitiativeSkill'] = false;
            MagicSkillService::getInstance()->init($enemyLineup['list'][$key]);
        }
        $enemySkill = ['tactical' => [], 'spirit' => [], 'magic' => array('pet' => 0)];


        $tmp = array(
            //mybuff=>["type":1,"heiheval存在的回合数":3,"ceng":1层数]存储技能释放buff
            //伤害数据       加血数据         我带的buff    反击
            'ismy' => true, 'type' => 0, 'shanghaiData' => [], 'buffdata' => [], 'mybuff' => [], 'id' => 0,
            'enemyShanghaiData' => [], 'enemyBuffdata' => [],
            //给敌人加的buff
            'enemybuff' => [], 'rewardCount' => 0,
            'spirit' => [], 'enemySpirit' => [], 'tactical' => [], 'enemyTactical' => [], 'magic' => [], 'enemyMagic' => [],
            //额外伤害 额外加血
            'extShanghaiData' => [], 'extBuffdata' => [],
            'extEnemyShanghaiData' => [], 'extEnemyBuffdata' => [],

            //双方扣除血量。self我方造成的伤害，enemy敌方造成的伤害
            'hurt' => ['self' => 0, 'enemy' => 0],
            //状态
            'status' => [], 'enemyStatus' => [],

            'revice' => []//复活参数 type：0精怪，1，战技，id索引

        );


        //计算出手顺序 写死
        $isFirst = $selfLineup['list'][$selfLineup['battle']]['speed'] >= $enemyLineup['list'][$enemyLineup['battle']]['speed'] ? 0 : 1;
        $selfRound = $enemyRound = false;

        //todo::测试技能
        //$selfSkill = ['spirit' => [40026 => 1, 40002 => 1, 40007 => 1, 40044 => 1, 40039 => 1], 'tactical' => [50001 => 1]];
        //处理复活次数
        $selfRevive = 0;//我方复活次数
        $enemyRevive = 0;//敌方复活次数
        if (isset($selfSkill['spirit'][40026]) || isset($selfSkill['magic'][133400])) $selfRevive++;


        //处理我方
        $tmpCopy = $tmp;
        $tmpCopy['round'] = $roundCount;

        //初始化妖力
        MagicSkillService::getInstance()->init($selfLineup['list'][$selfLineup['battle']]);

        //先处理首回合开始触发技能，包括只有我方
        if ($isFirst % 2 == 0) {
            $tmpCopy['ismy'] = true;
        } else {
            $tmpCopy['ismy'] = false;
        }

        $log = $tmpCopy;
        $log['type'] = 2;
        $log['hurt'] = ['self' => 0, 'enemy' => 0];

        $log['isFirst'] = $log['ismy'];//先出手
        //处理我方
        SkillService::getInstance()->triggerSkill($log, $selfSkill,
            $selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']],
            $selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']], 0);
        //每回合开始触发技能
        SkillService::getInstance()->triggerSkill($log, $selfSkill,
            $selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']],
            $selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']], 1);
        //第n回合开始触发技能
        SkillService::getInstance()->triggerSkill($log, $selfSkill,
            $selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']],
            $selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']],
            2, 'b');

        $selfShowData['hp'] = $selfLineup['list'][$selfLineup['battle']]['hp_max'];

        //处理血量
        if (!$log['ismy']) {
            AttributeComputeService::getInstance()->limitHpExtSub($enemyLineup['list'][$enemyLineup['battle']], $selfLineup['list'][$selfLineup['battle']], $log);
            $hurtList = [
                'self' => $log['hurt']['enemy'],
                'enemy' => $log['hurt']['self'],
            ];
            $log['hurt'] = $hurtList;
        } else {
            AttributeComputeService::getInstance()->limitHpExtSub($selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']], $log);
        }

        //处理血量
        $extHurtNameList = ['extShanghaiData', 'extEnemyShanghaiData'];
        if ($log['hurt']['self'] > 0 && $enemyLineup['list'][$enemyLineup['battle']]['hp'] > 0) {
            $delList = [];
            $this->getDieMonster($enemyLineup, $log['hurt']['self'], $delList, $enemySkill);
            $selfTotalHurt = add($selfTotalHurt, $log['hurt']['self']);
            $extHurtNameList = ['extEnemyShanghaiData', 'extShanghaiData'];
            MagicSkillService::getInstance()->addStaminaToAttacked($enemyLineup['list'][$enemyLineup['battle']], $log['hurt']['self']);
        }
        if ($log['hurt']['enemy'] > 0 && $selfLineup['list'][$selfLineup['battle']]['hp'] > 0) {
            $delList = [];
            $this->getDieMonster($selfLineup, $log['hurt']['enemy'], $delList, $selfSkill, $selfRevive);
            if ($selfRevive >= 0 && $selfLineup['list'][$selfLineup['battle']]['hp'] == 0) {
                $log[$extHurtNameList[1]][count($log[$extHurtNameList[1]]) - 1]['type'][] = BattleService::REVIVE;
            }
            MagicSkillService::getInstance()->addStaminaToAttacked($selfLineup['list'][$selfLineup['battle']], $log['hurt']['enemy']);
        }
        unset($log['isFirst'], $log['hurt']);


        $enemyHp = isset($enemyLineup['list'][$enemyLineup['battle']]) ? $enemyLineup['list'][$enemyLineup['battle']]['hp'] : 0;
        $myHp = isset($selfLineup['list'][$selfLineup['battle']]) ? $selfLineup['list'][$selfLineup['battle']]['hp'] : 0;
        if ($log['ismy']) {
            $log['hp'] = ['self' => $myHp, 'enemy' => $enemyHp];
            $log['battle'] = ['self' => $selfLineup['battle'], 'enemy' => $enemyLineup['battle']];
            $log['stamina'] = ['self' => $selfLineup['list'][$selfLineup['battle']]['stamina'], 'enemy' => 0];
        } else {
            $log['hp'] = ['self' => $enemyHp, 'enemy' => $myHp];
            $log['battle'] = ['self' => $enemyLineup['battle'], 'enemy' => $selfLineup['battle']];
            $log['stamina'] = ['self' => 0, 'enemy' => $selfLineup['list'][$selfLineup['battle']]['stamina']];
        }
        $battleLog[] = $log;

        unset($log['isFirst'], $log['hurt']);

        $myReviveTriggerPet = false;//复活触发宠物出手
        $enemyReviveTriggerPet = false;//复活触发宠物出手
        $myAttackTriggerPet = false;//反击触发宠物出手
        $enemyAttackTriggerPet = false;//反击触发宠物出手
        $myMagicTriggerPet = false;//道法触发宠物出手
        $enemyMagicTriggerPet = false;//道法触发宠物出手
        $myMagicTriggerRound = 0;
        $enemyMagicTriggerRound = 0;
        //获取技能22类型是否符合条件
        $selfType22Skill = SkillService::getInstance()->isTriggerSkillType22($selfSkill, $roundCount, $selfLineup['list'][$selfLineup['battle']]);
        $enemyType22Skill = SkillService::getInstance()->isTriggerSkillType22($enemySkill, $roundCount, $enemyLineup['list'][$enemyLineup['battle']]);

        //判断是否提高最大生命值
        if (isset($selfSkill['spirit'][40011])) {
            $selfLineup['hp_max'] = $selfLineup['list'][$selfLineup['battle']]['hp_max'];
        }

        while ($isContinue) {

            //处理每回合开始触发技能，第n回合开始触发技能
            $tmpCopy = $tmp;
            $tmpCopy['round'] = $roundCount;
            if ($isFirst % 2 == 0) {
                $tmpCopy['ismy'] = true;
            } else {
                $tmpCopy['ismy'] = false;
            }
            $log = $tmpCopy;
            $log['type'] = 2;
            $log['hurt'] = ['self' => 0, 'enemy' => 0];

            if (isset($selfLineup['list'][$selfLineup['battle']])) {
                //计算灵兽buff及debuff
                $selfPetBattleAttr = BattleService::getInstance()->getBattlePetEffectAttr($selfLineup['list'][$selfLineup['battle']], $roundCount);
                $enemyPetBattleAttr = BattleService::getInstance()->getBattlePetEffectAttr($enemyLineup['list'][$enemyLineup['battle']], $roundCount);

                //属性可能存在buff影响，每次重新计算
                $selfBattleAttr = AttributeComputeService::getInstance()->getBattleAttr($selfPetBattleAttr, $enemyPetBattleAttr);
                $enemyBattleAttr = AttributeComputeService::getInstance()->getBattleAttr($enemyPetBattleAttr, $selfPetBattleAttr);


                if (!$selfRound && !$enemyRound && $roundCount > 1) {

                    if ($log['ismy']) {
                        $log['isFirst'] = true;//先出手
                        //我方
                        //每回合开始触发技能
                        SkillService::getInstance()->triggerSkill($log, $selfSkill,
                            $selfBattleAttr, $enemyBattleAttr,
                            $selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']], 1);
                        //第n回合开始触发技能
                        SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfBattleAttr,
                            $enemyBattleAttr, $selfLineup['list'][$selfLineup['battle']],
                            $enemyLineup['list'][$enemyLineup['battle']], 2, 'b');

                        //处理道法状态,处理血量
                        MagicSkillService::getInstance()->triggerMagicStatus($log, $selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']], $selfBattleAttr, $enemyBattleAttr);
                        $log['isFirst'] = false;
                        //处理道法状态,
                        MagicSkillService::getInstance()->triggerMagicStatus($log, $enemyLineup['list'][$enemyLineup['battle']], $selfLineup['list'][$selfLineup['battle']], $enemyBattleAttr, $selfBattleAttr);


                    } else {
                        $log['isFirst'] = true;//先出手
                        //处理道法状态,
                        MagicSkillService::getInstance()->triggerMagicStatus($log, $enemyLineup['list'][$enemyLineup['battle']], $selfLineup['list'][$selfLineup['battle']], $enemyBattleAttr, $selfBattleAttr);

                        //处理我方
                        $log['isFirst'] = false;
                        //每回合开始触发技能
                        SkillService::getInstance()->triggerSkill($log, $selfSkill,
                            $selfBattleAttr, $enemyBattleAttr, $selfLineup['list'][$selfLineup['battle']],
                            $enemyLineup['list'][$enemyLineup['battle']], 1);
                        //第n回合开始触发技能
                        SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfBattleAttr, $enemyBattleAttr,
                            $selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']],
                            2, 'b');
                        //处理道法状态,处理血量
                        MagicSkillService::getInstance()->triggerMagicStatus($log, $selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']], $selfBattleAttr, $enemyBattleAttr);

                    }


                    $extHurtNameList = ['extShanghaiData', 'extEnemyShanghaiData'];
                    if (!$log['ismy']) {
                        AttributeComputeService::getInstance()->limitHpExtSub($enemyLineup['list'][$enemyLineup['battle']], $selfLineup['list'][$selfLineup['battle']], $log);
                        $hurtList = [
                            'self' => $log['hurt']['enemy'],
                            'enemy' => $log['hurt']['self'],
                        ];
                        $log['hurt'] = $hurtList;
                        $extHurtNameList = ['extEnemyShanghaiData', 'extShanghaiData'];
                    } else {
                        AttributeComputeService::getInstance()->limitHpExtSub($selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']], $log);
                    }

                    if ($log['hurt']['self'] > 0) {
                        $delList = [];
                        $this->getDieMonster($enemyLineup, $log['hurt']['self'], $delList, $enemySkill);
                        $selfTotalHurt = add($selfTotalHurt, $log['hurt']['self']);
                        MagicSkillService::getInstance()->addStaminaToAttacked($enemyLineup['list'][$enemyLineup['battle']], $log['hurt']['self']);
                    }
                    if ($log['hurt']['enemy'] > 0 && $selfLineup['list'][$selfLineup['battle']]['hp'] > 0) {
                        $delList = [];
                        $this->getDieMonster($selfLineup, $log['hurt']['enemy'], $delList, $selfSkill, $selfRevive);
                        if ($selfRevive >= 0 && $selfLineup ['list'][$selfLineup['battle']]['hp'] == 0) {
                            $log[$extHurtNameList[1]][count($log[$extHurtNameList[1]]) - 1]['type'][] = BattleService::REVIVE;
                        }
                        MagicSkillService::getInstance()->addStaminaToAttacked($selfLineup['list'][$selfLineup['battle']], $log['hurt']['enemy']);
                    }


                    unset($log['isFirst'], $log['hurt']);

                    $enemyHp = isset($enemyLineup['list'][$enemyLineup['battle']]) ? $enemyLineup['list'][$enemyLineup['battle']]['hp'] : 0;
                    $myHp = isset($selfLineup['list'][$selfLineup['battle']]) ? $selfLineup['list'][$selfLineup['battle']]['hp'] : 0;

                    $myStamina = isset($selfLineup['list'][$selfLineup['battle']]) ? $selfLineup['list'][$selfLineup['battle']]['stamina'] : 0;
                    if ($log['ismy']) {
                        $log['hp'] = ['self' => $myHp, 'enemy' => $enemyHp];
                        $log['battle'] = ['self' => $selfLineup['battle'], 'enemy' => $enemyLineup['battle']];
                        $log['stamina'] = ['self' => $myStamina, 'enemy' => 0];
                    } else {
                        $log['hp'] = ['self' => $enemyHp, 'enemy' => $myHp];
                        $log['battle'] = ['self' => $enemyLineup['battle'], 'enemy' => $selfLineup['battle']];
                        $log['stamina'] = ['self' => 0, 'enemy' => $myStamina];
                    }

                    $battleLog[] = $log;

                    //获取技能22类型是否符合条件
                    $selfType22Skill = SkillService::getInstance()->isTriggerSkillType22($selfSkill, $roundCount, $selfLineup['list'][$selfLineup['battle']]);
                    $enemyType22Skill = SkillService::getInstance()->isTriggerSkillType22($enemySkill, $roundCount, $enemyLineup['list'][$enemyLineup['battle']]);
                }


                if (isset($selfLineup['list'][$selfLineup['battle']])) {
                    //计算灵兽buff及debuff
                    $selfPetBattleAttr = BattleService::getInstance()->getBattlePetEffectAttr($selfLineup['list'][$selfLineup['battle']], $roundCount);
                    $enemyPetBattleAttr = BattleService::getInstance()->getBattlePetEffectAttr($enemyLineup['list'][$enemyLineup['battle']], $roundCount);

                    //属性可能存在buff影响，每次重新计算
                    $selfBattleAttr = AttributeComputeService::getInstance()->getBattleAttr($selfPetBattleAttr, $enemyPetBattleAttr);
                    $enemyBattleAttr = AttributeComputeService::getInstance()->getBattleAttr($enemyPetBattleAttr, $selfPetBattleAttr);


                    $tmpCopy = $tmp;
                    $tmpCopy['round'] = $roundCount;

                    if ($isFirst % 2 == 0) {
                        $tmpCopy['ismy'] = true;
                        BattleService::getInstance()->buffClear($selfLineup['list'][$selfLineup['battle']], $roundCount);
                        BattleService::getInstance()->buffClearEnemy($enemyLineup['list'][$enemyLineup['battle']], $roundCount);//清敌方自己身上状态
                        //$selfLineup['list'][$selfLineup['battle']]['attack'] = 9999999;
                        //副将出手
                        foreach ($selfPet as $petId => $petValue) {

                            if ((($roundCount - $myMagicTriggerRound) % $petValue['b']) == 0 || $myReviveTriggerPet || $myAttackTriggerPet || $myMagicTriggerPet) {
                                if ($enemyLineup['list'][$enemyLineup['battle']]['hp'] < 0) {
                                    $myReviveTriggerPet = true;
                                    continue;
                                }
                                if ($myMagicTriggerPet) {
                                    $myMagicTriggerRound = $roundCount;
                                }
                                $log = $this->petAttack($tmpCopy, $petValue, 0, $selfSkill, $enemySkill, $selfRevive, $enemyRevive,
                                    $selfBattleAttr, $enemyBattleAttr, $selfLineup, $enemyLineup, $selfType22Skill, $selfTotalHurt);
                                $battleLog[] = $log;
                                $myReviveTriggerPet = false;
                                $myAttackTriggerPet = false;
                                $myMagicTriggerPet = false;
                            }
                        }

                        //我方出手
                        $tmpCopy['type'] = 0;
                        $this->startBattle($selfLineup, $enemyLineup, $battleLog, $tmpCopy, $selfTotalHurt,
                            $enemyTotalHurt, $roundCount, $selfSkill, $enemySkill, $selfPet, $enemyPet, $selfRevive,
                            $enemyRevive, $myReviveTriggerPet, $enemyAttackTriggerPet, $myMagicTriggerPet, $myMagicTriggerRound,
                            $selfType22Skill, $enemyType22Skill);
                        $selfRound = true;
                    } else {
                        $tmpCopy['ismy'] = false;
                        //敌方不存在副将（灵兽）
                        BattleService::getInstance()->buffClear($selfLineup['list'][$selfLineup['battle']], $roundCount);
                        BattleService::getInstance()->buffClearEnemy($enemyLineup['list'][$enemyLineup['battle']], $roundCount);//清敌方自己身上状态
                        //敌方出手
                        $this->startBattle($enemyLineup, $selfLineup, $battleLog, $tmpCopy,
                            $enemyTotalHurt, $selfTotalHurt, $roundCount, $enemySkill, $selfSkill
                            , $enemyPet, $selfPet, $enemyRevive, $selfRevive,
                            $enemyAttackTriggerPet, $myReviveTriggerPet, $enemyMagicTriggerPet, $enemyMagicTriggerRound,
                            $enemyType22Skill, $selfType22Skill);
                        $enemyRound = true;
                    }
                }


            }

            //判断自己是否胜利
            if ($roundCount <= $roundLimit && !count($enemyLineup['list'])) {
                $isSuccess = true;
                $isContinue = false;
            }

            //回合数
            if ($selfRound === true && $enemyRound === true) {
                $roundCount++;
                $selfRound = $enemyRound = false;
            }

            //跳出战斗
            if (!isset($selfLineup['list'][$selfLineup['battle']])) {
                $isContinue = false;
            }

            //跳出战斗  回合数大于等限制  我方或地方人物全部阵亡
            if ($roundCount > $roundLimit || !count($selfLineup['list']) || !count($enemyLineup['list'])) $isContinue = false;

            $isFirst++;
        }

        $rewardCount = $enemyCount - count($enemyLineup['list']);


        foreach ($battleLog as $key => $value) {
            if ($value['ismy']) {
                $battleLog[$key]['rewardCount'] = $battleLog[$key]['battle']['enemy'] - 1;
            }
        }
        return [$isSuccess, $battleLog, $rewardCount, $selfTotalHurt];
    }


    public function startBattle(&$selfLineup, &$enemyLineup, &$battleLog, $tmp, &$selfTotalHurt, &$enemyTotalHurt, int $roundCount, $selfSkill, $enemySkill, $selfPet, $enemyPet, &$selfRevive, &$enemyRevive, &$myReviveTriggerPet, &$enemyAttackTriggerPet, &$myMagicTriggerPet, &$myMagicTriggerRound, &$selfType22Skill, &$enemyType22Skill)
    {
        $log = $tmp;
        $log['shanghaiData'] = ['type' => [], '_val' => '0'];
        $selfSkillCopy = $selfSkill;//处理132303技能，避免去除之后自动触发
        //判断是否复活
        if ($selfLineup['list'][$selfLineup['battle']]['hp'] <= 0 && $selfRevive > 0 && (!isset($selfLineup['list'][$selfLineup['battle']]['debuff']['freeze']) || isset($selfSkill['magic'][133400]))) {
            unset($selfLineup['list'][$selfLineup['battle']]['debuff']['stun']);
            SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfLineup['list'][$selfLineup['battle']],
                $enemyLineup['list'][$enemyLineup['battle']], $selfLineup['list'][$selfLineup['battle']],
                $enemyLineup['list'][$enemyLineup['battle']], 12);

            $selfRevive--;
            $log['shanghaiData']['type'][] = BattleService::TRIGGER_REVIVE;
        }


        //判断敌方是否还有血量
        if (!array_key_exists($enemyLineup['battle'], $enemyLineup['list'])) {
            return;
        }
        $myStamina = isset($selfLineup['list'][$selfLineup['battle']]) ? $selfLineup['list'][$selfLineup['battle']]['stamina'] : 0;
        $enemyHp = $enemyLineup['list'][$enemyLineup['battle']]['hp'];
        if ($enemyHp <= 0 && $enemyRevive > 0) {
            $log['hp'] = ['self' => $selfLineup['list'][$selfLineup['battle']]['hp'], 'enemy' => $enemyHp];
            if (!$log['shanghaiData']['type'] && !$log['shanghaiData']['_val']) {
                $log['shanghaiData'] = [];
            }

            $log['battle'] = ['self' => $selfLineup['battle'], 'enemy' => $enemyLineup['battle']];
            $log['stamina'] = ['self' => $myStamina, 'enemy' => $enemyLineup['list'][$enemyLineup['battle']]['stamina']];
            $battleLog[] = $log;
            return;
        }

        $selfBattleDetail = $selfLineup['list'][$selfLineup['battle']];
        $enemyBattlDetail = $enemyLineup['list'][$enemyLineup['battle']];
        //判断是否有击晕
        if (array_key_exists('stun', $selfBattleDetail['debuff']) || array_key_exists('freeze', $selfBattleDetail['debuff'])) {
            $log['hp'] = ['self' => $selfLineup['list'][$selfLineup['battle']]['hp'], 'enemy' => $enemyHp];
            if (!$log['shanghaiData']['type'] && !$log['shanghaiData']['_val']) {
                $log['shanghaiData'] = [];
            }
            $log['stamina'] = ['self' => $myStamina, 'enemy' => $enemyLineup['list'][$enemyLineup['battle']]['stamina']];
            $log['battle'] = ['self' => $selfLineup['battle'], 'enemy' => $enemyLineup['battle']];
            $battleLog[] = $log;
            return;
        }

        //计算灵兽buff及debuff
        $selfPetBattleAttr = BattleService::getInstance()->getBattlePetEffectAttr($selfBattleDetail, $roundCount);
        $enemyPetBattleAttr = BattleService::getInstance()->getBattlePetEffectAttr($enemyBattlDetail, $roundCount);

        //属性可能存在buff影响，每次重新计算
        $selfBattleAttr = AttributeComputeService::getInstance()->getBattleAttr($selfPetBattleAttr, $enemyPetBattleAttr);
        $enemyBattleAttr = AttributeComputeService::getInstance()->getBattleAttr($enemyPetBattleAttr, $selfPetBattleAttr);

        $isAttackMagic = false;
        if ($selfLineup['list'][$selfLineup['battle']]['magicInitiativeSkill'] && MagicSkillService::getInstance()->isStaminaMax($selfLineup['list'][$selfLineup['battle']])) {
            //计算道法伤害
            $hurt = 0;
            $skillData = ['id' => $selfLineup['list'][$selfLineup['battle']]['magicInitiativeSkill'], 'lv' => $selfSkill['magic'][$selfLineup['list'][$selfLineup['battle']]['magicInitiativeSkill']]];
            //触发道法主动技能
            MagicSkillService::getInstance()->triggerSkill($log, $skillData, $selfBattleAttr, $enemyBattleAttr,
                $selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']],
                $hurt, $selfSkill, $myMagicTriggerPet);

            $log['magic'][] = 0;
            $isAttackMagic = true;
            $log['shanghaiData']['type'][] = BattleService::MAGIC_ATTACK;
        } else {
            //计算普通伤害
            $hurt = AttributeComputeService::getInstance()->computeHit($selfBattleAttr['attack'],
                $enemyBattleAttr['defence'], $selfBattleAttr['final_hurt']);
            MagicSkillService::getInstance()->addStaminaToAttask($selfLineup['list'][$selfLineup['battle']]);
        }

        $isDodge = false;
        $log['hurt'] = ['self' => 0, 'enemy' => 0];
        //判定敌方闪避概率
        if ($isAttackMagic || isset($enemyBattlDetail['debuff']['stun']) || isset($enemyBattlDetail['debuff']['freeze']) || $enemyBattleAttr['dodge'] < rand(1, 1000)) {
            if ($hurt > 0) {
                $log['shanghaiData']['_val'] = add($hurt, $log['shanghaiData']['_val']);
                //暴击
                if ($selfBattleAttr['critical_hit'] >= rand(1, 1000)) {
                    BattleService::getInstance()->triggerCriticalHit($hurt, $log,
                        $selfBattleAttr['fortify_critical_hit'], $selfSkill, $selfBattleAttr,
                        $enemyBattleAttr, $selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']]);
                }

                //是否击晕对方
                if ($selfBattleAttr['stun'] >= rand(1, 1000)) {
                    BattleService::getInstance()->triggerStun($log, $enemyBattleAttr, $selfSkill, $selfBattleAttr,
                        $selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']]);
                }

                //吸血
                AttributeComputeService::getInstance()->limitHpSub($enemyLineup['list'][$enemyLineup['battle']], $hurt, $log);
                BattleService::getInstance()->triggerLifeSteal($selfBattleAttr, $hurt,
                    $selfLineup['list'][$selfLineup['battle']], $log);

                $selfTotalHurt = add($selfTotalHurt, $hurt);
                //如果伤害超出当前上阵敌人血量，上阵下一位
                $delList = [];
                $this->getDieMonster($enemyLineup, $hurt, $delList, $enemySkill, $enemyRevive);
            }

        } else {
            $hurt = 0;
            $isDodge = true;
            $log['shanghaiData']['type'][] = BattleService::DODGE;
            //每次闪避
            $log['isFirst'] = false;
            SkillService::getInstance()->triggerSkill($log, $enemySkill, $enemyBattleAttr,
                $selfBattleAttr, $enemyLineup['list'][$enemyLineup['battle']],
                $selfLineup['list'][$selfLineup['battle']], 10);
            unset($log['isFirst']);

            SkillService::getInstance()->triggerSkill($log, $selfSkill,
                $selfBattleAttr, $enemyBattleAttr, $selfLineup['list'][$selfLineup['battle']],
                $enemyLineup['list'][$enemyLineup['battle']], 24);

        }

        if ($hurt > 0) {
            MagicSkillService::getInstance()->addStaminaToAttacked($enemyLineup['list'][$enemyLineup['battle']], $hurt);
            if (!isset($enemyLineup['list'][$enemyLineup['battle']]) && $enemyRevive > 0 && (!isset($enemyLineup['list'][$enemyLineup['battle']]['debuff']['freeze']) || isset($enemyLineup['list'][$enemyLineup['battle']]['magic'][133400]))) {
                //代表可以复活
                $log['shanghaiData']['type'][] = BattleService::REVIVE;
                $log['extShanghaiData'] = [];
            }
        }

        //释放兵法时
        if ($isAttackMagic) {
            SkillService::getInstance()->triggerSkill($log, $selfSkill,  $selfBattleAttr,$enemyBattleAttr,
                $selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']], 19);
        }

        //攻击时,触发技能
        SkillService::getInstance()->attackTriggerSkill($log, $selfSkill, $selfBattleAttr, $enemyBattleAttr,
            $selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']]);

        //每次被攻击,触发技能
        if (isset($enemyLineup['list'][$enemyLineup['battle']])) {
            $log['isFirst'] = false;
            SkillService::getInstance()->triggerSkill($log, $enemySkill, $enemyBattleAttr, $selfBattleAttr,
                $enemyLineup['list'][$enemyLineup['battle']], $selfLineup['list'][$selfLineup['battle']], 11);
            unset($log['isFirst']);
        }


        SkillService::getInstance()->attackedTriggerSkill($log, $enemySkill, $selfBattleAttr, $enemyBattleAttr,
            $selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']]);


        //处理血量
        if ($log['hurt']['self'] > 0 && $enemyLineup['list'][$enemyLineup['battle']]['hp'] > 0) {
            $delList = [];
            $this->getDieMonster($enemyLineup, $log['hurt']['self'], $delList, $enemySkill, $enemyRevive);
            $selfTotalHurt = add($selfTotalHurt, $log['hurt']['self']);

            if (!$log['ismy'] && $enemyRevive > 0 && $enemyLineup['list'][$enemyLineup['battle']]['hp'] <= 0) {
                $log['extEnemyShanghaiData'][count($log['extEnemyShanghaiData']) - 1]['type'][] = BattleService::REVIVE;
            }
            MagicSkillService::getInstance()->addStaminaToAttacked($enemyLineup['list'][$enemyLineup['battle']], $log['hurt']['self']);
        }
        if ($log['hurt']['enemy'] > 0 && $selfLineup['list'][$selfLineup['battle']]['hp'] > 0) {
            $delList = [];
            $this->getDieMonster($selfLineup, $log['hurt']['enemy'], $delList, $selfSkill, $selfRevive);
            $enemyTotalHurt = add($enemyTotalHurt, $log['hurt']['enemy']);
            if ($log['ismy'] && $selfRevive > 0 && $selfLineup['list'][$selfLineup['battle']]['hp'] <= 0) {
                $log['extShanghaiData'][count($log['extShanghaiData']) - 1]['type'][] = BattleService::REVIVE;
            }
            MagicSkillService::getInstance()->addStaminaToAttacked($selfLineup['list'][$selfLineup['battle']], $log['hurt']['enemy']);
        }
        unset($log['hurt']);
        $myHp = isset($selfLineup['list'][$selfLineup['battle']]) ? $selfLineup['list'][$selfLineup['battle']]['hp'] : 0;
        $enemyHp = isset($enemyLineup['list'][$enemyLineup['battle']]) ? $enemyLineup['list'][$enemyLineup['battle']]['hp'] : 0;
        $myStamina = isset($selfLineup['list'][$selfLineup['battle']]) ? $selfLineup['list'][$selfLineup['battle']]['stamina'] : 0;
        $enemyStamina = isset($enemyLineup['list'][$enemyLineup['battle']]) ? $enemyLineup['list'][$enemyLineup['battle']]['stamina'] : 0;


        $log['hp'] = ['self' => $myHp, 'enemy' => $enemyHp];
        $log['battle'] = ['self' => $selfLineup['battle'], 'enemy' => $enemyLineup['battle']];
        $log['stamina'] = ['self' => $myStamina, 'enemy' => $enemyStamina];

        $battleLog[] = $log;
        $selfSkill = $selfSkillCopy;
        if ($enemyHp > 0) {
            //判断道法连击
            if ($isAttackMagic && $myHp > 0 && !isset($enemyLineup['list'][$enemyLineup['battle']]['debuff']['stun']) && !isset($enemyLineup['list'][$enemyLineup['battle']]['debuff']['freeze']) && $selfBattleAttr['magic_double_attack'] >= rand(1, 1000)) {
                $this->triggerMagicDoubleAttack($battleLog, $selfTotalHurt, $enemyTotalHurt, $tmp, $selfLineup, $enemyLineup, $selfBattleAttr, $enemyBattleAttr, $selfSkill, $enemySkill, $selfRevive, $enemyRevive);
            }


            $isAttackBack = false;
            if (!isset($enemyBattlDetail['debuff']['stun']) && !isset($enemyBattlDetail['debuff']['freeze']) && $enemyBattleAttr['attack_back'] >= rand(1, 1000)) {
                //触发反击
                $this->triggerEnemyAttackBack($selfBattleAttr['attack_back'],
                    floor($enemyBattleAttr['attack_back'] / 2), $selfLineup, $enemyLineup, $selfBattleAttr,
                    $enemyBattleAttr, $battleLog, $tmp, $selfSkill, $enemySkill, $selfTotalHurt, $enemyTotalHurt,
                    $selfPet, $enemyPet, $selfRevive, $enemyRevive,
                    $selfType22Skill, $enemyType22Skill, $myAttackTriggerPet, $enemyAttackTriggerPet);
                $isAttackBack = true;
            }
            $myHp = isset($selfLineup['list'][$selfLineup['battle']]) ? $selfLineup['list'][$selfLineup['battle']]['hp'] : 0;
            $enemyHp = isset($enemyLineup['list'][$enemyLineup['battle']]) ? $enemyLineup['list'][$enemyLineup['battle']]['hp'] : 0;
            if (!$isDodge && !$isAttackBack && $myHp > 0 && $enemyHp > 0 && !isset($selfLineup['list'][$selfLineup['battle']]['debuff']['stun']) && !isset($selfLineup['list'][$selfLineup['battle']]['debuff']['freeze']) && $selfBattleAttr['double_attack'] >= rand(1, 1000)) {
                if ($isAttackMagic) {
                    $isFirst = true;
                } else {
                    $isFirst = false;
                }
                //触发连击
                $this->triggerDoubleAttack(floor($selfBattleAttr['double_attack'] / 2), $selfLineup,
                    $enemyLineup, $enemyBattleAttr, $selfBattleAttr, $battleLog, $tmp, $selfSkill, $enemySkill,
                    $selfTotalHurt, $enemyTotalHurt, $selfPet, $enemyPet, $selfRevive, $enemyRevive,
                    $myReviveTriggerPet, $enemyAttackTriggerPet, $myMagicTriggerPet, $myMagicTriggerRound, $isFirst);
            }

        }
        $myHp = isset($selfLineup['list'][$selfLineup['battle']]) ? $selfLineup['list'][$selfLineup['battle']]['hp'] : 0;
        $enemyHp = isset($enemyLineup['list'][$enemyLineup['battle']]) ? $enemyLineup['list'][$enemyLineup['battle']]['hp'] : 0;
        //触发副将攻击
        if ($myHp > 0 && $enemyHp > 0 && $myMagicTriggerPet) {
            $log = $this->petAttack($tmp, $selfPet[0], 0, $selfSkill, $enemySkill, $selfRevive, $enemyRevive, $selfBattleAttr, $enemyBattleAttr, $selfLineup, $enemyLineup, $selfType22Skill, $selfTotalHurt);
            $myMagicTriggerRound = $log['round'];
            $battleLog[] = $log;
            $myMagicTriggerPet = 0;//是否触发副将出手
            $myReviveTriggerPet = false;
            $myMagicTriggerPet = false;
        }

    }

    //递归血量 待修改
    public function getDieMonster(&$enemyLineup, &$hurt, &$delList, $enemySkill, $selfRevive = 0): void
    {
        $hurtCopy = $hurt;
        foreach ($enemyLineup['list'] as $id => $detail) {
            if (in_array($id, $delList)) continue;

            $enemyLineup['hp'] = sub($enemyLineup['hp'], $hurt);

            if ($detail['hp'] > $hurt) {
                $enemyLineup['list'][$id]['hp'] = sub($detail['hp'], $hurt);
                break;
            } else {
                //如果大于0，触发复活，不延伸到下一位
                if ($selfRevive > 0 && (!isset($enemyLineup['list'][$id]['debuff']['freeze']) || isset($enemySkill['magic'][133400]))) {
                    $enemyLineup['list'][$id]['hp'] = 0;
                    $hurt = $hurtCopy;
                    break;
                }
            }

            $delList[] = $id;
            $hurt = sub($hurt, $detail['hp']);


            if (!count($enemyLineup['list'])) break;

            if (isset($enemyLineup['list'][$id + 1])) {
                //继承buff，debuffff
                //$selfLineup['buff'] || $selfLineup['debuff']
                //$selfLineup['buff_pet'] || $selfLineup['debuff_pet']
                if (isset($enemyLineup['list'][$id]['buff'])) {
                    $enemyLineup['list'][$id + 1]['buff'] = $enemyLineup['list'][$id]['buff'];
                }
                if (isset($enemyLineup['list'][$id]['debuff'])) {
                    $enemyLineup['list'][$id + 1]['debuff'] = $enemyLineup['list'][$id]['debuff'];
                }
                if (isset($enemyLineup['list'][$id]['buff_pet'])) {
                    $enemyLineup['list'][$id + 1]['buff_pet'] = $enemyLineup['list'][$id]['buff_pet'];
                }
                if (isset($enemyLineup['list'][$id]['debuff_pet'])) {
                    $enemyLineup['list'][$id + 1]['debuff_pet'] = $enemyLineup['list'][$id]['debuff_pet'];
                }
            }

            unset($enemyLineup['list'][$id]);
            //id 必须自增
            $enemyLineup['battle']++;

            $this->getDieMonster($enemyLineup, $hurt, $delList, $enemySkill);
        }
    }

    public function getBattleLineup($selfAttr): array
    {
        $lineup = [
            'battle' => null,
            'hp' => '0',
            'list' => [],
        ];

        //怪物血量固定，不需排序
        foreach ($selfAttr as $id => $detail) {

            if (is_null($lineup['battle'])) $lineup['battle'] = $id;
            $pet = isset($detail['pet']) ? $detail['pet'] : [];

            $selfAttr = BattleService::getInstance()->getRoleAttr([
                'lv' => $detail['lv'],
                'cloud' => $detail['cloud'],
                'equip' => $detail['equip'],
                'comrade' => $detail['comrade'],
                'chara' => $detail['chara'],
                'pet' => $pet,
                'spirit' => isset($detail['spirit']) ? $detail['spirit'] : [],
                'tactical' => isset($detail['tactical']) ? $detail['tactical'] : [],
                'equipment' => isset($detail['equipment']) ? $detail['equipment'] : [],
                'magic' => isset($detail['magic']) ? $detail['magic'] : [],
            ]);
            $selfAttr['buff'] = $selfAttr['debuff'] = $selfAttr['buff_pet'] = $selfAttr['debuff_pet'] = [];

            $selfAttr['buff_magic'] = [];
            $selfAttr['hp_max'] = $selfAttr['hp'];
            $selfAttr['pet'] = $pet;
            $lineup['hp'] = add($lineup['hp'], $selfAttr['hp']);
            $lineup['hp_max'] = $lineup['hp'];
            $lineup['list'][$id] = $selfAttr;

        }

        return $lineup;
    }


    public function triggerEnemyAttackBack(int $selfAttackBack, int $enemyAttackBack, &$selfLineup, &$enemyLineup, &$selfBattleAttr, &$enemyBattleAttr, &$battleLog, $tmp, $selfSkill, $enemySkill, &$selfTotalHurt, &$enemyTotalHurt, $selfPet, $enemyPet, $selfRevive, $enemyRevive, &$selfType22Skill, &$enemyType22Skill, &$myAttackTriggerPet, &$enemyAttackTriggerPet)
    {
        if (!isset($selfLineup['list'][$selfLineup['battle']])) {
            return;
        }
        if (!isset($enemyLineup['list'][$enemyLineup['battle']])) {
            return;
        }
        $enemySkillCopy = $enemySkill;
        $selfBattleDetail = $selfLineup['list'][$selfLineup['battle']];
        $enemyBattlDetail = $enemyLineup['list'][$enemyLineup['battle']];

        //计算灵兽buff及debuff
        $selfPetBattleAttr = BattleService::getInstance()->getBattlePetEffectAttr($selfBattleDetail, $tmp['round']);
        $enemyPetBattleAttr = BattleService::getInstance()->getBattlePetEffectAttr($enemyBattlDetail, $tmp['round']);

        //属性可能存在buff影响，每次重新计算
        $selfBattleAttr = AttributeComputeService::getInstance()->getBattleAttr($selfPetBattleAttr, $enemyPetBattleAttr);
        $enemyBattleAttr = AttributeComputeService::getInstance()->getBattleAttr($enemyPetBattleAttr, $selfPetBattleAttr);


        $isTriggerPet = 0;//是否触发副将出手
        $battleLog[count($battleLog) - 1]['shanghaiData']['type'][] = BattleService::ATTACK_BACK;//代表下一条反击
        $tmp['ismy'] = !$tmp['ismy'];
        $log = $tmp;
        $log['hurt'] = ['self' => 0, 'enemy' => 0];
        //计算普通伤害
        $backHurt = AttributeComputeService::getInstance()->computeHit($enemyBattleAttr['attack'],
            $selfBattleAttr['defence'], $enemyBattleAttr['final_hurt']);
        $log['shanghaiData'] = ['type' => [BattleService::TRIGGER_ATTACK_BACK], '_val' => $backHurt];//代表这一条为反击触发
        $isDodge = false;

        //我方是否触发闪避
        if (isset($selfLineup['list'][$selfLineup['battle']]['debuff']['stun']) || isset($selfLineup['list'][$selfLineup['battle']]['debuff']['freeze']) || $selfBattleAttr['dodge'] < rand(1, 1000)) {
            //敌方是否触发暴击
            if ($enemyBattleAttr['critical_hit'] >= rand(1, 1000)) {
                BattleService::getInstance()->triggerCriticalHit($backHurt, $log,
                    $enemyBattleAttr['fortify_critical_hit'], $enemySkill, $enemyBattleAttr, $selfBattleAttr,
                    $enemyLineup['list'][$enemyLineup['battle']], $selfLineup['list'][$selfLineup['battle']]);
            }

            //敌方是否触发击晕
            if ($enemyBattleAttr['stun'] >= rand(1, 1000)) {
                BattleService::getInstance()->triggerStun($log, $selfBattleAttr,
                    $enemySkill, $enemyBattleAttr, $enemyLineup['list'][$enemyLineup['battle']],
                    $selfLineup['list'][$selfLineup['battle']]);
            }
            //吸血
            AttributeComputeService::getInstance()->limitHpSub($selfLineup['list'][$selfLineup['battle']], $backHurt, $log);
            BattleService::getInstance()->triggerLifeSteal($enemyBattleAttr, $backHurt,
                $enemyLineup['list'][$enemyLineup['battle']], $log);


            //计算伤害
            $enemyTotalHurt = add($enemyTotalHurt, $backHurt);
            //如果伤害超出当前上阵敌人血量，上阵下一位
            $delList = [];
            $this->getDieMonster($selfLineup, $backHurt, $delList, $selfSkill, $selfRevive);
            MagicSkillService::getInstance()->addStaminaToAttacked($selfLineup['list'][$selfLineup['battle']], $backHurt);

        } else {
            $isDodge = true;
            $log['shanghaiData']['type'][] = BattleService::DODGE;
            $log['shanghaiData']['_val'] = 0;
            //每次闪避
            $log['isFirst'] = false;
            SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfBattleAttr, $enemyBattleAttr,
                $selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']], 10);
            unset($log['isFirst']);

            SkillService::getInstance()->triggerSkill($log, $enemySkill,
                $enemyBattleAttr, $selfBattleAttr, $enemyLineup['list'][$enemyLineup['battle']],
                $selfLineup['list'][$selfLineup['battle']], 24);

        }

        if (!isset($selfLineup['list'][$selfLineup['battle']])) {
            $enemyHp = isset($enemyLineup['list'][$enemyLineup['battle']]) ? $enemyLineup['list'][$enemyLineup['battle']]['hp'] : 0;;
            $enemyStamina = isset($enemyLineup['list'][$enemyLineup['battle']]) ? $enemyLineup['list'][$enemyLineup['battle']]['stamina'] : 0;


            $log['hp'] = ['self' => $enemyHp, 'enemy' => 0];
            $log['battle'] = ['self' => $enemyLineup['battle'], 'enemy' => $selfLineup['battle']];
            $log['stamina'] = ['self' => $enemyStamina, 'enemy' => 0];
            $battleLog[] = $log;
            return;
        }

        if (!$isDodge) {//反击命中后
            SkillService::getInstance()->triggerSkill($log, $enemySkill, $enemyBattleAttr, $selfBattleAttr,
                $enemyLineup['list'][$enemyLineup['battle']], $selfLineup['list'][$selfLineup['battle']], 18);
        }


        //攻击时,触发技能
        SkillService::getInstance()->attackTriggerSkill($log, $enemySkill, $enemyBattleAttr, $selfBattleAttr,
            $enemyLineup['list'][$enemyLineup['battle']], $selfLineup['list'][$selfLineup['battle']]);
        //反击时
        SkillService::getInstance()->triggerSkill($log, $enemySkill, $enemyBattleAttr, $selfBattleAttr,
            $enemyLineup['list'][$enemyLineup['battle']], $selfLineup['list'][$selfLineup['battle']],
            6, false, $enemyAttackTriggerPet);

        //每次被攻击,触发技能
        $log['isFirst'] = false;
        SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfBattleAttr, $enemyBattleAttr,
            $selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']], 11);
        unset($log['isFirst']);
        SkillService::getInstance()->attackedTriggerSkill($log, $selfSkill, $enemyBattleAttr, $selfBattleAttr,
            $enemyLineup['list'][$enemyLineup['battle']], $selfLineup['list'][$selfLineup['battle']]);


        //处理血量
        AttributeComputeService::getInstance()->limitHpExtSub($enemyLineup['list'][$enemyLineup['battle']], $selfLineup['list'][$selfLineup['battle']]['hp'], $log);
        if ($log['hurt']['self'] > 0 && $selfLineup['list'][$selfLineup['battle']]['hp'] > 0) {
            $delList = [];
            $this->getDieMonster($selfLineup, $log['hurt']['self'], $delList, $selfSkill, $selfRevive);
            $enemyTotalHurt = add($enemyTotalHurt, $log['hurt']['self']);
            if ($log['ismy'] && $selfRevive > 0 && $selfLineup['list'][$selfLineup['battle']]['hp'] <= 0) {
                $log['extShanghaiData'][count($log['extShanghaiData']) - 1]['type'][] = BattleService::REVIVE;
            }
            MagicSkillService::getInstance()->addStaminaToAttacked($selfLineup['list'][$selfLineup['battle']], $log['hurt']['self']);
        }
        if ($log['hurt']['enemy'] > 0 && $enemyLineup['list'][$enemyLineup['battle']]['hp'] > 0) {
            $delList = [];
            $this->getDieMonster($enemyLineup, $log['hurt']['enemy'], $delList, $enemySkill, $enemyRevive);
            $selfTotalHurt = add($selfTotalHurt, $log['hurt']['enemy']);
            if (!$log['ismy'] && $enemyRevive > 0 && $enemyLineup['list'][$enemyLineup['battle']]['hp'] <= 0) {
                $log['extEnemyShanghaiData'][count($log['extEnemyShanghaiData']) - 1]['type'][] = BattleService::REVIVE;
            }
            MagicSkillService::getInstance()->addStaminaToAttacked($enemyLineup['list'][$enemyLineup['battle']], $log['hurt']['enemy']);
        }
        unset($log['hurt']);
        $myHp = isset($selfLineup['list'][$selfLineup['battle']]) ? $selfLineup['list'][$selfLineup['battle']]['hp'] : 0;
        $enemyHp = isset($enemyLineup['list'][$enemyLineup['battle']]) ? $enemyLineup['list'][$enemyLineup['battle']]['hp'] : 0;
        $myStamina = isset($selfLineup['list'][$selfLineup['battle']]) ? $selfLineup['list'][$selfLineup['battle']]['stamina'] : 0;
        $enemyStamina = isset($enemyLineup['list'][$enemyLineup['battle']]) ? $enemyLineup['list'][$enemyLineup['battle']]['stamina'] : 0;

        $log['hp'] = ['self' => $enemyHp, 'enemy' => $myHp];
        $log['battle'] = ['self' => $enemyLineup['battle'], 'enemy' => $selfLineup['battle']];
        $log['stamina'] = ['self' => $enemyStamina, 'enemy' => $myStamina];
        $battleLog[] = $log;
        $enemySkill = $enemySkillCopy;
        if ($myHp > 0) {
            //代表触发副将出手
            if ($isTriggerPet > 0 && isset($enemyPet[0])) {
                $log = $this->petAttack($tmp, $enemyPet[0], 0, $enemySkill, $selfSkill, $enemyRevive, $selfRevive, $enemyBattleAttr, $selfBattleAttr, $enemyLineup, $selfLineup, $enemyType22Skill, $enemyTotalHurt, $enemyAttackTriggerPet);
                $battleLog[] = $log;
                $enemyAttackTriggerPet = false;//是否触发副将出手
            }

            if (!isset($selfLineup['list'][$selfLineup['battle']]['debuff']['stun']) && !isset($selfLineup['list'][$selfLineup['battle']]['debuff']['freeze']) && $selfAttackBack >= rand(1, 1000)) {
                $this->triggerEnemyAttackBack($enemyAttackBack, floor($selfAttackBack / 2), $enemyLineup, $selfLineup,
                    $enemyBattleAttr, $selfBattleAttr, $battleLog, $tmp, $enemySkill, $selfSkill,
                    $enemyTotalHurt, $selfTotalHurt, $enemyPet, $selfPet, $enemyRevive, $selfRevive,
                    $enemyType22Skill, $selfType22Skill, $enemyAttackTriggerPet, $myAttackTriggerPet);
            }
        } else {
            if ($selfRevive > 0) {
                $battleLog[count($battleLog) - 1]['shanghaiData']['type'][] = BattleService::REVIVE;
            }
        }

    }


    public function triggerDoubleAttack(int $doubleAttack, &$selfLineup, &$enemyLineup, &$enemyBattleAttr, &$selfBattleAttr, &$battleLog, $tmp, $selfSkill, $enemySkill, &$selfTotalHurt, &$enemyTotalHurt, $selfPet, $enemyPet, $selfRevive, $enemyRevive, &$myReviveTriggerPet, &$enemyAttackTriggerPet, &$myMagicTriggerPet, &$myMagicTriggerRound, $isFirst = false): void
    {

        if (!array_key_exists($selfLineup['battle'], $selfLineup['list'])) {
            return;
        }

        if (!array_key_exists($enemyLineup['battle'], $enemyLineup['list'])) {
            return;
        }
        $selfSkillCopy = $selfSkill;

        $selfBattleDetail = $selfLineup['list'][$selfLineup['battle']];
        $enemyBattlDetail = $enemyLineup['list'][$enemyLineup['battle']];

        //计算灵兽buff及debuff
        $selfPetBattleAttr = BattleService::getInstance()->getBattlePetEffectAttr($selfBattleDetail, $tmp['round']);
        $enemyPetBattleAttr = BattleService::getInstance()->getBattlePetEffectAttr($enemyBattlDetail, $tmp['round']);

        //属性可能存在buff影响，每次重新计算
        $selfBattleAttr = AttributeComputeService::getInstance()->getBattleAttr($selfPetBattleAttr, $enemyPetBattleAttr);
        $enemyBattleAttr = AttributeComputeService::getInstance()->getBattleAttr($enemyPetBattleAttr, $selfPetBattleAttr);

        //反击触发连击
        // $battleLog[count($battleLog) - 1]['shanghaiData']['type'][] = BattleService::ATTACK_BACK_AND_DOUBLE_ATTACK;

        $log = $tmp;
        //反击触发连击
        if ($isFirst) {
            $log['shanghaiData'] = ['type' => [BattleService::TRIGGER_DOUBLE_ATTACK, BattleService::MAGIC_ATTACK_TRIGGER_DOUBLE_ATTACK], '_val' => '0'];//为连击触发的数据
        } else {
            $battleLog[count($battleLog) - 1]['shanghaiData']['type'][] = BattleService::DOUBLE_ATTACK;//下一条为连击
            $log['shanghaiData'] = ['type' => [BattleService::TRIGGER_DOUBLE_ATTACK], '_val' => '0'];//为连击触发的数据
        }

        //计算普通伤害
        $hurt = AttributeComputeService::getInstance()->computeHit($selfBattleAttr['attack'],
            $enemyBattleAttr['defence'], $selfBattleAttr['final_hurt']);

        $isDodge = false;
        //判定敌方闪避概率
        if (isset($enemyLineup['list'][$enemyLineup['battle']]['debuff']['stun']) || isset($enemyLineup['list'][$enemyLineup['battle']]['debuff']['freeze']) || $enemyBattleAttr['dodge'] < rand(1, 1000)) {
            //暴击
            if ($selfBattleAttr['critical_hit'] >= rand(1, 1000)) {
                BattleService::getInstance()->triggerCriticalHit($hurt, $log, $selfBattleAttr['fortify_critical_hit'],
                    $selfSkill, $selfBattleAttr, $enemyBattleAttr,
                    $selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']]);
            }

            //是否击晕对方
            if (!isset($selfLineup['list'][$selfLineup['battle']]['debuff']['stun']) && !isset($selfLineup['list'][$selfLineup['battle']]['debuff']['freeze']) && $selfBattleAttr['stun'] >= rand(1, 1000)) {
                BattleService::getInstance()->triggerStun($log, $enemyBattleAttr,
                    $selfSkill, $selfBattleAttr, $selfLineup['list'][$selfLineup['battle']],
                    $enemyLineup['list'][$enemyLineup['battle']]);
            }
            //吸血
            AttributeComputeService::getInstance()->limitHpSub($enemyLineup['list'][$enemyLineup['battle']], $hurt, $log);
            BattleService::getInstance()->triggerLifeSteal($selfBattleAttr, $hurt,
                $selfLineup['list'][$selfLineup['battle']], $log);

            $selfTotalHurt = add($selfTotalHurt, $hurt);
            //如果伤害超出当前上阵敌人血量，上阵下一位
            $delList = [];
            $this->getDieMonster($enemyLineup, $hurt, $delList, $enemySkill, $enemyRevive);

            $log['shanghaiData']['_val'] = $hurt;
            MagicSkillService::getInstance()->addStaminaToAttacked($enemyLineup['list'][$enemyLineup['battle']], $hurt);
        } else {
            $isDodge = true;
            $log['shanghaiData']['type'][] = BattleService::DODGE;
            $log['shanghaiData']['_val'] = 0;
            //每次闪避
            $log['isFirst'] = false;
            SkillService::getInstance()->triggerSkill($log, $enemySkill, $enemyBattleAttr, $selfBattleAttr,
                $enemyLineup['list'][$enemyLineup['battle']], $selfLineup['list'][$selfLineup['battle']], 10);
            unset($log['isFirst']);
            SkillService::getInstance()->triggerSkill($log, $selfSkill,
                $selfBattleAttr, $enemyBattleAttr, $selfLineup['list'][$selfLineup['battle']],
                $enemyLineup['list'][$enemyLineup['battle']], 24);

        }

        if (!isset($enemyLineup['list'][$enemyLineup['battle']])) {
            $myHp = isset($selfLineup['list'][$selfLineup['battle']]) ? $selfLineup['list'][$selfLineup['battle']]['hp'] : 0;
            $myStamina = isset($selfLineup['list'][$selfLineup['battle']]) ? $selfLineup['list'][$selfLineup['battle']]['stamina'] : 0;


            $log['hp'] = ['self' => $myHp, 'enemy' => 0];
            $log['battle'] = ['self' => $selfLineup['battle'], 'enemy' => $enemyLineup['battle']];
            $log['stamina'] = ['self' => $myStamina, 'enemy' => 0];
            $battleLog[] = $log;
            return;
        }

        //攻击时,触发技能
        SkillService::getInstance()->attackTriggerSkill($log, $selfSkill, $selfBattleAttr, $enemyBattleAttr,
            $selfLineup['list'][$selfLineup['battle']],
            $enemyLineup['list'][$enemyLineup['battle']]);
        //每次触发连击
        SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfBattleAttr, $enemyBattleAttr,
            $selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']], 3);

        //每次被攻击,触发技能
        $log['isFirst'] = false;
        SkillService::getInstance()->triggerSkill($log, $enemySkill, $enemyBattleAttr, $selfBattleAttr,
            $enemyLineup['list'][$enemyLineup['battle']], $selfLineup['list'][$selfLineup['battle']], 11);
        unset($log['isFirst']);

        SkillService::getInstance()->attackedTriggerSkill($log, $enemySkill, $selfBattleAttr, $enemyBattleAttr,
            $selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']]);


        //处理血量
        if ($log['hurt']['self'] > 0 && $enemyLineup['list'][$enemyLineup['battle']]['hp'] > 0) {
            $delList = [];
            $this->getDieMonster($enemyLineup, $log['hurt']['self'], $delList, $enemySkill, $enemyRevive);
            $selfTotalHurt = add($selfTotalHurt, $log['hurt']['self']);

            if (!$log['ismy'] && $enemyRevive > 0 && $enemyLineup['list'][$enemyLineup['battle']]['hp'] <= 0) {
                $log['extEnemyShanghaiData'][count($log['extEnemyShanghaiData']) - 1]['type'][] = BattleService::REVIVE;
            }
        }
        if ($log['hurt']['enemy'] > 0 && $selfLineup['list'][$selfLineup['battle']]['hp'] > 0) {
            $delList = [];
            $this->getDieMonster($selfLineup, $log['hurt']['enemy'], $delList, $selfSkill, $selfRevive);
            $enemyTotalHurt = add($enemyTotalHurt, $log['hurt']['enemy']);

            if ($log['ismy'] && $selfRevive > 0 && $selfLineup['list'][$selfLineup['battle']]['hp'] <= 0) {
                $log['extShanghaiData'][count($log['extShanghaiData']) - 1]['type'][] = BattleService::REVIVE;
            }
        }
        unset($log['hurt']);
        $myHp = isset($selfLineup['list'][$selfLineup['battle']]) ? $selfLineup['list'][$selfLineup['battle']]['hp'] : 0;
        $enemyHp = isset($enemyLineup['list'][$enemyLineup['battle']]) ? $enemyLineup['list'][$enemyLineup['battle']]['hp'] : 0;
        $myStamina = isset($selfLineup['list'][$selfLineup['battle']]) ? $selfLineup['list'][$selfLineup['battle']]['stamina'] : 0;
        $enemyStamina = isset($enemyLineup['list'][$enemyLineup['battle']]) ? $enemyLineup['list'][$enemyLineup['battle']]['stamina'] : 0;

        $log['hp'] = ['self' => $myHp, 'enemy' => $enemyHp];
        $log['battle'] = ['self' => $selfLineup['battle'], 'enemy' => $enemyLineup['battle']];
        $log['stamina'] = ['self' => $myStamina, 'enemy' => $enemyStamina];

        $selfSkill = $selfSkillCopy;
        if ($enemyHp > 0) {
            $battleLog[] = $log;

            $selfBattleDetail = $selfLineup['list'][$selfLineup['battle']];
            $enemyBattlDetail = $enemyLineup['list'][$enemyLineup['battle']];

            //计算灵兽buff及debuff
            $selfPetBattleAttr = BattleService::getInstance()->getBattlePetEffectAttr($selfBattleDetail, $log['round']);
            $enemyPetBattleAttr = BattleService::getInstance()->getBattlePetEffectAttr($enemyBattlDetail, $log['round']);

            //属性可能存在buff影响，每次重新计算
            $selfBattleAttr = AttributeComputeService::getInstance()->getBattleAttr($selfPetBattleAttr, $enemyPetBattleAttr);
            $enemyBattleAttr = AttributeComputeService::getInstance()->getBattleAttr($enemyPetBattleAttr, $selfPetBattleAttr);

            $isAttackBack = false;
            if (!isset($enemyBattlDetail['debuff']['stun']) && $enemyBattleAttr['attack_back'] >= rand(1, 1000)) {
                //触发反击
                $this->triggerEnemyAttackBack($selfBattleAttr['attack_back'],
                    floor($enemyBattleAttr['attack_back'] / 2), $selfLineup, $enemyLineup, $selfBattleAttr,
                    $enemyBattleAttr, $battleLog, $tmp, $selfSkill, $enemySkill, $selfTotalHurt, $enemyTotalHurt,
                    $selfPet, $enemyPet, $selfRevive, $enemyRevive, $selfType22Skill, $enemyType22Skill, $myAttackTriggerPet, $enemyAttackTriggerPet);
                $isAttackBack = true;
            }
            $myHp = isset($selfLineup['list'][$selfLineup['battle']]) ? $selfLineup['list'][$selfLineup['battle']]['hp'] : 0;
            $enemyHp = isset($enemyLineup['list'][$enemyLineup['battle']]) ? $enemyLineup['list'][$enemyLineup['battle']]['hp'] : 0;

            if (!$isDodge && !$isAttackBack && $myHp > 0 && $enemyHp > 0 && !isset($selfLineup['list'][$selfLineup['battle']]['debuff']['stun']) && $doubleAttack >= rand(1, 1000)) {
                //触发连击
                $this->triggerDoubleAttack(floor($doubleAttack / 2), $selfLineup,
                    $enemyLineup, $enemyBattleAttr, $selfBattleAttr, $battleLog, $tmp, $selfSkill, $enemySkill,
                    $selfTotalHurt, $enemyTotalHurt, $selfPet, $enemyPet, $selfRevive, $enemyRevive,
                    $myReviveTriggerPet, $enemyAttackTriggerPet, $myMagicTriggerPet, $myMagicTriggerRound);
            }

        } else {
            //代表可以复活
            if ($enemyRevive > 0) {
                $log['shanghaiData']['type'][] = BattleService::REVIVE;
            }
            $battleLog[] = $log;
        }
    }


    //副将攻击
    public function petAttack($tmp, $petValue, $id, $selfSkill, $enemySkill, $selfRevive, $enemyRevive, &$selfBattleAttr, &$enemyBattleAttr, &$selfLineup, &$enemyLineup, &$selfType22Skill, &$selfTotalHurt, $isTriggerPet = false)
    {
        $selfBattleDetail = $selfLineup['list'][$selfLineup['battle']];
        $enemyBattlDetail = $enemyLineup['list'][$enemyLineup['battle']];

        //计算灵兽buff及debuff
        $selfPetBattleAttr = BattleService::getInstance()->getBattlePetEffectAttr($selfBattleDetail, $tmp['round']);
        $enemyPetBattleAttr = BattleService::getInstance()->getBattlePetEffectAttr($enemyBattlDetail, $tmp['round']);

        //属性可能存在buff影响，每次重新计算
        $selfBattleAttr = AttributeComputeService::getInstance()->getBattleAttr($selfPetBattleAttr, $enemyPetBattleAttr);
        $enemyBattleAttr = AttributeComputeService::getInstance()->getBattleAttr($enemyPetBattleAttr, $selfPetBattleAttr);

        list($subHp, $log) = SkillService::getInstance()->petAttackSkill($selfBattleAttr,
            $enemyBattleAttr, $selfLineup['list'][$selfLineup['battle']],
            $enemyLineup['list'][$enemyLineup['battle']], $tmp, $petValue, $id, $selfSkill);

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

        //如果伤害超出当前上阵敌人血量，上阵下一位
        if ($subHp > 0) {
            AttributeComputeService::getInstance()->limitHpSub($enemyLineup['list'][$enemyLineup['battle']], $subHp, $log);
            $delList = [];
            $this->getDieMonster($enemyLineup, $subHp, $delList, $enemySkill);
            if ($enemyLineup['list'][$enemyLineup['battle']]['hp'] <= 0 && $enemyRevive > 0 && (!isset($enemyLineup['list'][$enemyLineup['battle']]['debuff']['freeze']) || isset($enemySkill['magic'][133400]))) {
                $log['shanghaiData']['type'][] = BattleService::REVIVE;
            }
            MagicSkillService::getInstance()->addStaminaToAttacked($enemyLineup['list'][$enemyLineup['battle']], $subHp);
        }

        $log['hurt'] = ['self' => 0, 'enemy' => 0];

        if (!in_array($petValue['id'], SkillService::PET_NO_ATTASK_SKILL_LIST)) {//副将每次攻击后触发技能
            SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfBattleAttr,
                $enemyBattleAttr, $selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']], 21);
            if (MagicSkillService::getInstance()->isNegativeStatus($enemyLineup['list'][$enemyLineup['battle']])) {
                SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfBattleAttr,
                    $enemyBattleAttr, $selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']], 20);
            }
        }


        $selfTotalHurt = add($selfTotalHurt, $subHp);


        SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfBattleAttr, $enemyBattleAttr,
            $selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']], 7);

        if ($selfType22Skill) {
            $skillList = SkillService::getInstance()->formatSkillList($selfType22Skill);
            SkillService::getInstance()->triggerSkill($log, $skillList, $selfBattleAttr,
                $enemyBattleAttr, $selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']], 22);
            $selfType22Skill = [];
        }
        //被攻击时
        SkillService::getInstance()->attackedTriggerSkill($log, $enemySkill, $selfBattleAttr, $enemyBattleAttr, $selfDetail, $enemyDetail);

        //处理血量
        AttributeComputeService::getInstance()->limitHpExtSub($selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']], $log);
        if ($log['hurt']['self'] > 0 && $enemyLineup['list'][$enemyLineup['battle']]['hp'] > 0) {
            $delList = [];
            $this->getDieMonster($enemyLineup, $log['hurt']['self'], $delList, $enemySkill);
            $enemyHp = isset($enemyLineup['list'][$enemyLineup['battle']]) ? $enemyLineup['list'][$enemyLineup['battle']]['hp'] : 0;
            if ($enemyHp <= 0 && $enemyRevive > 0 && (!isset($enemyLineup['list'][$enemyLineup['battle']]['debuff']['freeze']) || isset($enemySkill['magic'][133400]))) {
                $log['extShanghaiData'][count($log['extShanghaiData']) - 1]['type'][] = BattleService::REVIVE;
            }
            MagicSkillService::getInstance()->addStaminaToAttacked($enemyLineup['list'][$enemyLineup['battle']], $log['hurt']['self']);
        }
        if ($log['hurt']['enemy'] > 0 && $selfLineup['list'][$selfLineup['battle']]['hp'] > 0) {
            $delList = [];
            $this->getDieMonster($selfLineup, $log['hurt']['enemy'], $delList, $selfSkill, $selfRevive);
            $myHp = isset($selfLineup['list'][$selfLineup['battle']]) ? $selfLineup['list'][$selfLineup['battle']]['hp'] : 0;
            if ($selfRevive >= 0 && $myHp == 0) {
                $log['extEnemyShanghaiData'][count($log['extEnemyShanghaiData']) - 1]['type'][] = BattleService::REVIVE;
            }
            MagicSkillService::getInstance()->addStaminaToAttacked($selfLineup['list'][$selfLineup['battle']], $log['hurt']['enemy']);
        }
        unset($log['hurt']);

        $enemyHp = isset($enemyLineup['list'][$enemyLineup['battle']]) ? $enemyLineup['list'][$enemyLineup['battle']]['hp'] : 0;
        $enemyStamina = isset($enemyLineup['list'][$enemyLineup['battle']]) ? $enemyLineup['list'][$enemyLineup['battle']]['stamina'] : 0;
        $log['hp'] = ['self' => $selfLineup['list'][$selfLineup['battle']]['hp'], 'enemy' => $enemyHp];
        $log['battle'] = ['self' => $selfLineup['battle'], 'enemy' => $enemyLineup['battle']];
        $log['stamina'] = ['self' => $selfLineup['list'][$selfLineup['battle']]['stamina'], 'enemy' => $enemyStamina];

        return $log;
    }


    //道法连击
    public function triggerMagicDoubleAttack(&$battleLog, &$selfTotalHurt, &$enemyTotalHurt, $tmp, &$selfLineup, &$enemyLineup, &$selfBattleAttr, &$enemyBattleAttr, $selfSkill, $enemySkill, $selfRevive, $enemyRevive)
    {

        if (!isset($selfLineup['list'][$selfLineup['battle']])) {
            return;
        }
        if (!isset($enemyLineup['list'][$enemyLineup['battle']])) {
            return;
        }

        $selfBattleDetail = $selfLineup['list'][$selfLineup['battle']];
        $enemyBattlDetail = $enemyLineup['list'][$enemyLineup['battle']];

        //计算灵兽buff及debuff
        $selfPetBattleAttr = BattleService::getInstance()->getBattlePetEffectAttr($selfBattleDetail, $tmp['round']);
        $enemyPetBattleAttr = BattleService::getInstance()->getBattlePetEffectAttr($enemyBattlDetail, $tmp['round']);

        //属性可能存在buff影响，每次重新计算
        $selfBattleAttr = AttributeComputeService::getInstance()->getBattleAttr($selfPetBattleAttr, $enemyPetBattleAttr);
        $enemyBattleAttr = AttributeComputeService::getInstance()->getBattleAttr($enemyPetBattleAttr, $selfPetBattleAttr);

        //反击道法连击
        $battleLog[count($battleLog) - 1]['shanghaiData']['type'][] = BattleService::MAGIC_DOUBLE_ATTACK;//下一条为道法连击


        //触发后仅造成道法一定的伤害和治疗，无法触发特殊效果（比如冰封效果、灼烧效果），每次释放道法仅会触发一次道法连击。
        $log = $tmp;
        $log['shanghaiData'] = ['type' => [BattleService::TRIGGER_MAGIC_DOUBLE_ATTACK], '_val' => '0'];//下一条为触发道法连击
        //计算道法伤害
        $hurt = 0;
        $skillData = ['id' => $selfLineup['list'][$selfLineup['battle']]['magicInitiativeSkill'], 'lv' => $selfSkill['magic'][$selfLineup['list'][$selfLineup['battle']]['magicInitiativeSkill']]];
        //触发道法主动技能
        MagicSkillService::getInstance()->triggerSkill($log, $skillData, $selfBattleAttr, $enemyBattleAttr,
            $selfDetail, $enemyDetail, $hurt, $selfSkill, $myMagicTriggerPet);
        $log['magic'][] = 0;
        $log['shanghaiData']['type'][] = BattleService::MAGIC_ATTACK;

        if ($hurt > 0) {
            $log['shanghaiData']['_val'] = add($hurt, $log['shanghaiData']['_val']);
            //暴击
            if ($selfBattleAttr['critical_hit'] >= rand(1, 1000)) {
                BattleService::getInstance()->triggerCriticalHit($hurt, $log,
                    $selfBattleAttr['fortify_critical_hit'], $selfSkill, $selfBattleAttr,
                    $enemyBattleAttr, $selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']]);
            }

            //是否击晕对方
            if ($selfBattleAttr['stun'] >= rand(1, 1000)) {
                BattleService::getInstance()->triggerStun($log, $enemyBattleAttr, $selfSkill, $selfBattleAttr,
                    $selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']]);
            }

            //吸血
            AttributeComputeService::getInstance()->limitHpSub($enemyLineup['list'][$enemyLineup['battle']], $hurt, $log);
            BattleService::getInstance()->triggerLifeSteal($selfBattleAttr, $hurt,
                $selfLineup['list'][$selfLineup['battle']], $log);

            $selfTotalHurt = add($selfTotalHurt, $hurt);
            //如果伤害超出当前上阵敌人血量，上阵下一位
            $delList = [];
            $this->getDieMonster($enemyLineup, $hurt, $delList, $enemySkill, $enemyRevive);
            MagicSkillService::getInstance()->addStaminaToAttacked($enemyLineup['list'][$enemyLineup['battle']], $hurt);
        }


        //释放兵法时
        SkillService::getInstance()->triggerSkill($log, $selfSkill,  $selfBattleAttr,$enemyBattleAttr,
            $selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']], 19);


        //攻击时,触发技能
        SkillService::getInstance()->attackTriggerSkill($log, $selfSkill, $selfBattleAttr, $enemyBattleAttr,
            $selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']]);

        //每次被攻击,触发技能
        if (isset($enemyLineup['list'][$enemyLineup['battle']])) {
            $log['isFirst'] = false;
            SkillService::getInstance()->triggerSkill($log, $enemySkill, $enemyBattleAttr, $selfBattleAttr,
                $enemyLineup['list'][$enemyLineup['battle']], $selfLineup['list'][$selfLineup['battle']], 11);
            unset($log['isFirst']);
        }


        SkillService::getInstance()->attackedTriggerSkill($log, $enemySkill, $selfBattleAttr, $enemyBattleAttr,
            $selfLineup['list'][$selfLineup['battle']], $enemyLineup['list'][$enemyLineup['battle']]);


        //处理血量
        if ($log['hurt']['self'] > 0 && $enemyLineup['list'][$enemyLineup['battle']]['hp'] > 0) {
            $delList = [];
            $this->getDieMonster($enemyLineup, $log['hurt']['self'], $delList, $enemySkill, $enemyRevive);
            $selfTotalHurt = add($selfTotalHurt, $log['hurt']['self']);

            if (!$log['ismy'] && $enemyRevive > 0 && $enemyLineup['list'][$enemyLineup['battle']]['hp'] <= 0) {
                $log['extEnemyShanghaiData'][count($log['extEnemyShanghaiData']) - 1]['type'][] = BattleService::REVIVE;
            }
            MagicSkillService::getInstance()->addStaminaToAttacked($enemyLineup['list'][$enemyLineup['battle']], $log['hurt']['self']);
        }
        if ($log['hurt']['enemy'] > 0 && $selfLineup['list'][$selfLineup['battle']]['hp'] > 0) {
            $delList = [];
            $this->getDieMonster($selfLineup, $log['hurt']['enemy'], $delList, $selfSkill, $selfRevive);
            $enemyTotalHurt = add($enemyTotalHurt, $log['hurt']['enemy']);
            if ($log['ismy'] && $selfRevive > 0 && $selfLineup['list'][$selfLineup['battle']]['hp'] <= 0) {
                $log['extShanghaiData'][count($log['extShanghaiData']) - 1]['type'][] = BattleService::REVIVE;
            }
            MagicSkillService::getInstance()->addStaminaToAttacked($selfLineup['list'][$selfLineup['battle']], $log['hurt']['enemy']);
        }
        unset($log['hurt']);
        $myHp = isset($selfLineup['list'][$selfLineup['battle']]) ? $selfLineup['list'][$selfLineup['battle']]['hp'] : 0;
        $enemyHp = isset($enemyLineup['list'][$enemyLineup['battle']]) ? $enemyLineup['list'][$enemyLineup['battle']]['hp'] : 0;
        $myStamina = isset($selfLineup['list'][$selfLineup['battle']]) ? $selfLineup['list'][$selfLineup['battle']]['stamina'] : 0;
        $enemyStamina = isset($enemyLineup['list'][$enemyLineup['battle']]) ? $enemyLineup['list'][$enemyLineup['battle']]['stamina'] : 0;

        $log['hp'] = ['self' => $myHp, 'enemy' => $enemyHp];
        $log['battle'] = ['self' => $selfLineup['battle'], 'enemy' => $enemyLineup['battle']];
        $log['stamina'] = ['self' => $myStamina, 'enemy' => $enemyStamina];
        $battleLog[] = $log;

    }


}