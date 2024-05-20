<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Validate\ValidateClass;

class ApiStatus
{
    use CoroutineSingleTon;

    protected $tableName = 'api_status';

    public function create():void
    {
        $methods = ValidateClass::getInstance()->getMethods();

        $columns = [
            'error'    => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
        ];

        foreach ($methods as $name) 
        {
            $columns[ $name ] = [ 'type'=> Table::TYPE_INT ,'size'=> 8 ];
        }

        TableManager::getInstance()->add( $this->tableName , $columns , 100000 );
    }

    public function incr(string $column):void
    {
        $table = TableManager::getInstance()->get($this->tableName);
        $time  = time();
        if(!$table->exist( $time ))
        {
            $methods = ValidateClass::getInstance()->getMethods();
            $data = [ 'error'    => 0 ];
            foreach ($methods as $name) 
            {
                $data[ $name ] = 0;
            }
            $table->set( $time,$data );
        }

        $table->incr(time(),$column,1);
    }

    public function del():void
    {
        $table = TableManager::getInstance()->get($this->tableName);
        $keys = [];
        $time = time();
        foreach ($table as $key => $item) 
        {
            if($key >= $time ) continue;
            $keys[] = $key;
        }

        foreach ($keys as $key)
        {
            $table->del($key);
        }
    }

    public function getInfo(int $begin,int $end,int $top):array
    {
        $list  = [];
        $log   = [];
        $qps   = [];
        $table = TableManager::getInstance()->get($this->tableName);

        foreach ($table as $key => $item) 
        {
            if($key < $begin || $key > $end) continue;
            $log[$key] = $item;
            foreach ($item as $method => $count) 
            {
                if(!array_key_exists($method,$list)) $list[$method] = 0;
                $list[$method] += $count; 
            }
            
        }
        
        arsort($list);
        $topInfo = array_slice($list, 0, $top);
        foreach ($log as $time => $detail) 
        {
            foreach ($detail as $method => $count) 
            {
                if(!array_key_exists($method,$topInfo)) continue;
                $qps[date('Y-m-d H:i:s',$time)][$method] = $count;
            }
        }

        return [$topInfo,$qps];
    }

    public function show(array $param):string
    {
        $top       = array_key_exists('t',$param) && is_numeric($param['t']) ? $param['t'] : 10;
        $beginDate = array_key_exists('bd',$param) ? $param['bd'] : date('Y-m-d H:i:s');
        $endDate   = array_key_exists('ed',$param) ? $param['ed'] : date('Y-m-d H:i:s');

        $beginTime = strtotime($beginDate);
        $endTime   = strtotime($endDate);

        if(!$beginTime || !$endTime || $beginTime > $endTime) $beginTime = $endTime = time();

        list($data,$qps) = $this->getInfo($beginTime,$endTime,$top);

        $final = [];
        foreach ($data as $key => $val)
        {
            $final[] = [
                'date'   => date('Y-m-d H:i:s',$beginTime).' - '.date('Y-m-d H:i:s',$endTime),
                'method' => $key,
                'count'  => $val
            ];
        }

        return json_encode(['sum' => $final,'qps' => $qps],JSON_UNESCAPED_UNICODE) ;
    }

}
