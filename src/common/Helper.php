<?php

namespace spider\common;

abstract class Helper
{
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
}