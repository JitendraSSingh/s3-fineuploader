<?php
use App\Controllers\HomeController;
$app->get('/', HomeController::class . ':index')->setName('home');
$app->get('/test', HomeController::class . ':test')->setName('test');
$app->post('/sign-policy', HomeController::class . ':signPolicyDocument')->setName('sign.policy');
$app->post('/verify-upload', HomeController::class . ':verifyUpload')->setName('verify.upload');
$app->post('/delete-upload', HomeController::class . ':deleteUpload')->setName('delete.upload');