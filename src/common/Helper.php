<?php

namespace spider\common;

use Yii;

abstract class Helper
{
    /**
     * 获取 query 上的参数
     * @param $qs
     * @param $key
     * @param null $default
     * @return null
     */
    public static function getQsParam($qs, $key, $default = null)
    {
        $params = parse_url($qs, PHP_URL_QUERY);
        $data1 = explode('&', $params);
        foreach ($data1 as $i) {
            $data2 = explode('=', $i);
            if ($data2[0] == $key) {
                return $data2[1];
            }
        }
        return $default;
    }

    /**
     * 获取存储路径
     * @param $relativePath
     * @return bool|string
     */
    public static function getStoragePath($relativePath)
    {
        return Yii::getAlias(Yii::$app->params[ConfigString::PARAM_STORAGE_PATH] . '/' . ltrim($relativePath, '/'));
    }
}