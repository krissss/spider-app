<?php

namespace spider\common;

use Yii;
use yii\db\Connection;
use yii\queue\Queue;

class ConfigString
{
    const COMPONENT_QUEUE = 'queue';

    const LOG_HOU_NIAO = 'houniao';

    /**
     * @return null|object|Connection
     * @throws \yii\base\InvalidConfigException
     */
    public static function getDb()
    {
        return Yii::$app->get('db');
    }

    /**
     * @return null|object|Queue
     * @throws \yii\base\InvalidConfigException
     */
    public static function getQueue()
    {
        return Yii::$app->get(static::COMPONENT_QUEUE);
    }
}