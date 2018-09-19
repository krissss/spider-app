<?php

namespace spider\common;

use Yii;
use yii\helpers\Json;

class Logger
{
    /**
     * @param $msg
     * @param string $type
     */
    public static function houniao($msg, $type = 'info')
    {
        static::write($msg, $type, ConfigString::LOG_HOU_NIAO);
    }

    /**
     * 写入日志
     * @param $msg
     * @param $type
     * @param $category
     */
    protected static function write($msg, $type, $category)
    {
        $msg = is_array($msg) ? Json::encode($msg) : $msg;
        Yii::$type($msg, $category);
        if (is_string($msg)) {
            echo $msg . PHP_EOL;
        }
    }

    /**
     * 获取日志存储路径
     * @param $category
     * @param bool $noDate
     * @return string
     */
    public static function getCommonLogDir($category, $noDate = false)
    {
        $log = "@runtime/logs/{$category}/{$category}.log";
        if ($noDate) {
            return $log;
        } else {
            $date = date('Ymd');
            return $log . ".{$date}";
        }
    }
}