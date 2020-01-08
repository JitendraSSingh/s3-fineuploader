<?php

require __DIR__ . '/vendor/autoload.php';
$config = [
    'settings' => [
        'displayErrorDetails' => true,
    ],
];
$app = new \Slim\App($config);
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();