<?php
return [
//    'language' => 'pl',
//    'sourceLanguage' => 'pl',
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
    ],
];
