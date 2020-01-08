<?php
use App\Controllers\HomeController;
use App\Upload;
use Aws\S3\S3Client;
use Aws\Credentials\Credentials;

$container = $app->getContainer();

// Register component on container
$container['view'] = function ($container) {
    $view = new \Slim\Views\Twig(__DIR__ . '/resources', [
        'cache' => false
    ]);

    // Instantiate and add Slim specific extension
    $router = $container->get('router');
    $uri = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($_SERVER));
    $view->addExtension(new \Slim\Views\TwigExtension($router, $uri));

    return $view;
};

$container[S3Client::class] = function($c){
    $credentials = new Credentials(getenv('AWS_ACCESS_KEY_ID'),getenv('AWS_SECRET_ACCESS_KEY'));
    return new S3Client([
        'version' => '2006-03-01',
        'region' => 'ap-southeast-2',
        'credentials' => $credentials
    ]);
};

$container[Upload::class] = function($c){
    return new Upload($c->get(S3Client::class));
};

$container[HomeController::class] = function($c){
    return new HomeController($c->get('view'), $c->get(Upload::class));
};