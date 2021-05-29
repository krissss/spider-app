<?php

use spider\common\ConfigString;

return [
    'components' => [
        'db' => [
            'class' => \yii\db\Connection::class,
            'dsn' => 'mysql:host=127.0.0.1;dbname=spider_app',
            'username' => 'root',
            'password' => 'root',
            'charset' => 'utf8mb4',
            'enableSchemaCache' => true,
            'schemaCacheDuration' => 60,
            'schemaCacheExclude' => [],
            'schemaCache' => 'cache',
            'queryCache' => 'cache',
        ],
    ],
    'params' => [
        ConfigString::PARAM_STORAGE_PATH => '@runtime/storage'
    ],
];