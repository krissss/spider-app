<?php

namespace spider\common;

class StringHelper extends \yii\helpers\StringHelper
{
    public static function rtrim($str, $trim)
    {
        if (static::endsWith($str, $trim)) {
            return mb_substr($str, 0, -mb_strlen($trim), 'utf-8');
        }
        return $str;
    }
}