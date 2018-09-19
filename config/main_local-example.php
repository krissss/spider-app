<?php

use spider\common\ConfigString;

return [
    'components' => [
        'db' => [
            'class' => \yii\db\Connection::class,
            'dsn' => 'mysql:host=127.0.0.1;dbname=spider_houniao',
            'username' => 'root',
            'password' => 'root',
            'charset' => 'utf8',
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