<?php

use spider\common\ConfigString;
use spider\common\Logger;

$basePath = __DIR__ . '/../src';
$runtimePath = __DIR__ . '/../runtime';
$dbPath = __DIR__ . '/../db';

Yii::setAlias('@spider', $basePath);
Yii::setAlias('@runtime', $runtimePath);

return [
    'id' => 'spider',
    'basePath' => $basePath,
    'controllerNamespace' => 'spider\controllers',
    'runtimePath' => $runtimePath,
    'bootstrap' => ['log', ConfigString::COMPONENT_QUEUE],
    'components' => [
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
];