<?php

use spider\common\ConfigString;
use spider\common\Logger;

$basePath = __DIR__ . '/../src';
$runtimePath = __DIR__ . '/../runtime';

Yii::setAlias('@spider', $basePath);
Yii::setAlias('@runtime', $runtimePath);

return [
    'id' => 'spider',
    'basePath' => $basePath,
    'controllerNamespace' => 'spider\controllers',
    'runtimePath' => $runtimePath,
    'bootstrap' => ['log', ConfigString::COMPONENT_QUEUE],
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => get_env('DB_DSN'),
            'username' => get_env('DB_USERNAME'),
            'password' => get_env('DB_PASSWORD'),
            'charset' => 'utf8mb4',
            'enableSchemaCache' => true,
            'schemaCacheDuration' => 3600,
            'schemaCacheExclude' => [],
            'schemaCache' => 'cache',
            'queryCache' => 'cache',
            'enableLogging' => YII_DEBUG,
            'enableProfiling' => YII_DEBUG,
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    'except' => [
                        'yii\web\HttpException:401',
                    ],
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'categories' => [ConfigString::LOG_HOU_NIAO],
                    'logVars' => [],
                    'logFile' => Logger::getCommonLogDir(ConfigString::LOG_HOU_NIAO),
                    'maxLogFiles' => 31,
                    'dirMode' => 0777,
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'categories' => [\yii\queue\Queue::class],
                    'logVars' => [],
                    'logFile' => Logger::getCommonLogDir(\yii\queue\Queue::class),
                    'maxLogFiles' => 31,
                    'dirMode' => 0777,
                ],
            ],
        ],
        ConfigString::COMPONENT_QUEUE => [
            'class' => \yii\queue\file\Queue::class,
            'as log' => \yii\queue\LogBehavior::class,
        ],
    ],
    'params' => [
        ConfigString::PARAM_STORAGE_PATH => '@runtime/storage'
    ],
];