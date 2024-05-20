<?php
namespace app\service\data;
use app\model\LogGuide;

class LogPropService
{
    public static function getGuideData(array $appConfig , array $param,array $config):array
    {
        if(!$selectData = LogGuide::getGuideList($appConfig,$param)) return $selectData;
        return self::getGuideShowFormartData($selectData,$config);
    }


    public static function getGuideShowFormartData(array $selectData,array $config)
    {
        $transferArr = [];

        foreach ($selectData as $key => $value) 
        {
            $transferArr[$value['cid']][$value['type']] = $value;
        }
        unset($key,$value);

        $startCount = 0;
        $returnArr  = [];

        foreach ($config as $key => $value) 
        {
            if(!array_key_exists($key,$transferArr)) continue;

            $start   = array_key_exists(1,$transferArr[$key]) ? $transferArr[$key][1]['numbers'] + 0 : 0;
            $success = array_key_exists(2,$transferArr[$key]) ? $transferArr[$key][2]['numbers'] + 0 : 0;
            $startCount ? : $startCount = $start;

            $returnArr[] = array(
                'guideId'        => $key,
                'desc'           => $value,
                'start'          => $start,
                'success'        => $success,
                'successRatio'   => $success && $start ? sprintf("%.2f",$success / $start * 100) + 0 : 0,
                'rty'            => $success && $startCount ? sprintf("%.2f",$success / $startCount * 100) + 0 : 0,
            );
        }
        return $returnArr;
    }
}
