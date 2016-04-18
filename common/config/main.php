<?php
return [
    'language' => 'pl',
    'sourceLanguage' => 'pl',
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'config' => [
            'class' => 'common\components\Config',
            'cacheComponentId'  => 'cache',
            'cacheId'           => 'global_app_settings',
            'cacheTime'         => 86400, //24h
            'tableName'         => '{{%config}}',
            'dbComponentId'     => 'db',
        ],
        'api' => [
            'class' => 'common\components\Api',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',
            'useFileTransport' => false,
        ],
    ],
];
