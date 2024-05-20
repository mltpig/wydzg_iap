<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigNickname as Model;

class ConfigNickname
{
    use CoroutineSingleTon;

    protected $tableName1 = 'config_nickname_surname';
    protected $tableName2 = 'config_nickname_name';
    protected $tableName3 = 'config_nickname_title';

    public function create():void
    {
        $columns = [
            'val' => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
        ];

        TableManager::getInstance()->add( $this->tableName1 , $columns , 2000 );
        TableManager::getInstance()->add( $this->tableName2 , $columns , 2000 );
        TableManager::getInstance()->add( $this->tableName3 , $columns , 2000 );

    }

    public function initTable():void
    {
        $table1 = TableManager::getInstance()->get($this->tableName1);
        $table2 = TableManager::getInstance()->get($this->tableName2);
        $table3 = TableManager::getInstance()->get($this->tableName3);

        $tableConfig = Model::create()->all();
        $size1 = $size2 = $size3 = 1;
        foreach ($tableConfig as $key => $value) 
        {

            if($value['surname'])
            {
                $table1->set($size1,[ 'val' => $value['surname'] ]);
                $size1++;
            }

            if($value['name'])
            {
                $table2->set($size2,[ 'val' => $value['name'] ]);
                $size2++;
            }

            if($value['title'])
            {
                $table3->set($size3,[ 'val' => $value['title'] ]);
                $size3++;
            } 
        }

    }

    public function getOne(string $tablename,int $index):string
    {
        $table = TableManager::getInstance()->get($tablename);
        if(!$table->count()) $this->initTable();
        
        $data = $table->get($index);
        return $data ? $data['val'] : '';
    }

    public function getSize(string $tablename):string
    {
        $table = TableManager::getInstance()->get($tablename);

        if(!$table->count()) $this->initTable();
        
        return $table->count();
    }

    public function getNickname():string
    {
        $size1 = $this->getSize($this->tableName1);
        $size2 = $this->getSize($this->tableName2);
        $size3 = $this->getSize($this->tableName3);
        $surname = $this->getOne($this->tableName1,rand(1,$size1));
        $num = rand(1,2);
        $name = '';
        for ($i= $num; $i < 3; $i++) 
        { 
            $name .= $this->getOne($this->tableName2,rand(1,$size2));
        }

        $title = rand(0,1) ? $this->getOne($this->tableName3,rand(1,$size3)) : '';

        return $surname.$name.$title;
    }
}
