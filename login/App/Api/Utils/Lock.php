<?php

namespace App\Api\Utils;
use EasySwoole\Component\TableManager;

class Lock
{

    static function exists(string $roleid):bool
    {
        //roleid 本身就是唯一
        $table = TableManager::getInstance()->get(TABLE_UID_LOCK);

        if( $table->exist($roleid) ) return true;
        
        $table->set($roleid,['is' => 1]);

        return false;
    }
    
    static function rem(string $roleid):void
    {
        TableManager::getInstance()->get(TABLE_UID_LOCK)->del($roleid);
    }

}
