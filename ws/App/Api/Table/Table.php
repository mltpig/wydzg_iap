<?php
namespace App\Api\Table;
use EasySwoole\Component\CoroutineSingleTon;
use Swoole\Table as SwTable;
use EasySwoole\Component\TableManager;
use App\Api\Table\Activity\FirstRecharge as ActivityFirstRecharge;
use App\Api\Table\Activity\OptionalGiftbag as ActivityOptionalGiftbag;
use App\Api\Table\Activity\SignIn as ActivitySignIn;
use App\Api\Table\Activity\NewYear as ActivityNewYear;
use App\Api\Table\Activity\ConfigFund as ConfigFund;
use App\Api\Table\Activity\ConfigActivityDaily as ConfigActivityDaily;

class Table
{
    use CoroutineSingleTon;

    public function create():void
    {

        TableManager::getInstance()->add(TABLE_FD_UID, ['uid' =>[ 'type'=> SwTable::TYPE_STRING,'size'=> 100 ],'site' =>[ 'type'=> SwTable::TYPE_INT,'size'=> 8 ] ],100000);
        TableManager::getInstance()->add(TABLE_UID_FD, ['fd' =>[ 'type'=> SwTable::TYPE_INT,'size'=> 8 ],'scene' =>[ 'type'=> SwTable::TYPE_STRING,'size'=> 4 ]],100000);
        TableManager::getInstance()->add(TABLE_UID_LOCK, [ 'state' =>[ 'type'=> SwTable::TYPE_INT,'size'=> 8 ]],100000);
        
        ConfigEquipSpecial::getInstance()->create();
        ConfigTree::getInstance()->create();
        ConfigParam::getInstance()->create();
        ConfigRole::getInstance()->create();
        ConfigEquipBase::getInstance()->create();
        ConfigEquipAttach::getInstance()->create();
        ConfigChapter::getInstance()->create();
        ConfigTask::getInstance()->create();
        ConfigNickname::getInstance()->create();
        ConfigCloud::getInstance()->create();
        ConfigCloudStage::getInstance()->create();
        ConfigRoleChara::getInstance()->create();
        ActivityFirstRecharge::getInstance()->create();
        ConfigShop::getInstance()->create();
        ConfigDoufaRobot::getInstance()->create();
        ActivityOptionalGiftbag::getInstance()->create();
        ConfigGoods::getInstance()->create();
        ConfigChallengeBoss::getInstance()->create();
        ConfigMonsterInvade::getInstance()->create();
        ActivitySignIn::getInstance()->create();
        ConfigParadiseLevel::getInstance()->create();
        ConfigParadiseReward::getInstance()->create();
        ConfigComrade::getInstance()->create();
        ConfigSkill::getInstance()->create();
        ConfigComradeVisit::getInstance()->create();
        ConfigComradeChallenge::getInstance()->create();
        ConfigMonsters::getInstance()->create();
        ConfigMonstersLevel::getInstance()->create();
        ActivityNewYear::getInstance()->create();
        ConfigPets::getInstance()->create();
        ConfigSkillRandom::getInstance()->create();
        ConfigGoodsBox::getInstance()->create();
        // ConfigPetsAdvance::getInstance()->create();
        ConfigCombine::getInstance()->create();
        ConfigSpirits::getInstance()->create();

        //阵法相关
        ConfigTalentLevel::getInstance()->create();
        ConfigTalentCreate::getInstance()->create();
        ConfigTalent::getInstance()->create();
        ConfigTalentBook::getInstance()->create();

        ConfigTower::getInstance()->create();
        ConfigEquipmentAdvance::getInstance()->create();
        ConfigEquipmentAdvanceUp::getInstance()->create();
        ConfigSystemInfo::getInstance()->create();
        ConfigSecretTower::getInstance()->create();
        ApiStatus::getInstance()->create();
        ConfigMagic::getInstance()->create();
        ConfigMagicLevelUp::getInstance()->create();
        ConfigStone::getInstance()->create();
        ConfigPaid::getInstance()->create();
        ConfigFund::getInstance()->create();
        ConfigActivityDaily::getInstance()->create();
    }

    public function reset():void
    {
        echo "缓存初始化开始".date('Y-m-d H:i:s').PHP_EOL ;

        ConfigEquipSpecial::getInstance()->initTable();
        ConfigTree::getInstance()->initTable();
        ConfigParam::getInstance()->initTable();
        ConfigRole::getInstance()->initTable();
        ConfigEquipBase::getInstance()->initTable();
        ConfigEquipAttach::getInstance()->initTable();
        ConfigChapter::getInstance()->initTable();
        ConfigTask::getInstance()->initTable();
        ConfigNickname::getInstance()->initTable();
        ConfigCloud::getInstance()->initTable();
        ConfigCloudStage::getInstance()->initTable();
        ConfigRoleChara::getInstance()->initTable();
        ActivityFirstRecharge::getInstance()->initTable();
        ConfigShop::getInstance()->initTable();
        ActivityOptionalGiftbag::getInstance()->initTable();
        ConfigGoods::getInstance()->initTable();
        ConfigChallengeBoss::getInstance()->initTable();
        ConfigMonsterInvade::getInstance()->initTable();
        ActivitySignIn::getInstance()->initTable();
        ConfigParadiseLevel::getInstance()->initTable();
        ConfigParadiseReward::getInstance()->initTable();
        ConfigComrade::getInstance()->initTable();
        ConfigSkill::getInstance()->initTable();
        ConfigComradeVisit::getInstance()->initTable();
        ConfigComradeChallenge::getInstance()->initTable();
        ConfigMonsters::getInstance()->initTable();
        ConfigMonstersLevel::getInstance()->initTable();
        ActivityNewYear::getInstance()->initTable();
        ConfigPets::getInstance()->initTable();
        ConfigSkillRandom::getInstance()->initTable();
        ConfigGoodsBox::getInstance()->initTable();
        // ConfigPetsAdvance::getInstance()->initTable();
        ConfigCombine::getInstance()->initTable();
        ConfigSpirits::getInstance()->initTable();

        //阵法相关
        ConfigTalentLevel::getInstance()->initTable();
        ConfigTalentCreate::getInstance()->initTable();
        ConfigTalent::getInstance()->initTable();
        ConfigTalentBook::getInstance()->initTable();

        ConfigTower::getInstance()->initTable();
        ConfigEquipmentAdvance::getInstance()->initTable();
        ConfigEquipmentAdvanceUp::getInstance()->initTable();
        ConfigSystemInfo::getInstance()->initTable();
        ConfigSecretTower::getInstance()->initTable();
        ConfigMagic::getInstance()->initTable();
        ConfigMagicLevelUp::getInstance()->initTable();
        ConfigStone::getInstance()->initTable();
        ConfigPaid::getInstance()->initTable();
        ConfigFund::getInstance()->initTable();
        ConfigActivityDaily::getInstance()->initTable();

        echo "缓存初始化结束".date('Y-m-d H:i:s').PHP_EOL ;
    }

}
