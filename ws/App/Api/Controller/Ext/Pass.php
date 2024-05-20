<?php
namespace App\Api\Controller\Ext;
use EasySwoole\EasySwoole\Core;
use App\Api\Service\TaskService;
use App\Api\Controller\BaseController;

class Pass  extends BaseController
{

    public function index()
    { 
        if(Core::getInstance()->runMode() === 'dev')
        {
            $this->player->passChapter();

            $chapter = intval($this->player->getData('chapter'));

            TaskService::getInstance()->setVal($this->player,3,$chapter-1,'set');
        }
        $this->sendMsg(['chapterid' => intval($this->player->getData('chapter'))]);
    }

}