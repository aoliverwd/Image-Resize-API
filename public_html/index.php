<?php

use AOWD\envLoader\ResourceLoader;
use ImageServe\Controllers\App;
use AOWD\Router;

// Composer autoloader
require_once dirname(__DIR__) . "/vendor/autoload.php";

// Load environment variables
ResourceLoader::applyEnvironmentVariables(dirname(__DIR__) . "/.env");

// New router instance
$router = new Router();

/**
 * Image resize API endpoint
 * image/width=80,quality=75/test.jpeg
 */
$router->register(
    "GET",
    "/image/([a-zA-Z]+=[a-zA-Z0-9]+,?)+/[0-9A-Za-z-_+]+.(jpg|jpeg|png|webp)",
    fn() => App::processMiddleware(function () {
        $router = new Router();
        parse_str(str_replace(",", "&", $router->getSegment(1)), $options);

        App::loadAsset($router->getSegment(2), $options);
    })
);

// Register error handler
$router->register404(function () {
    echo "404 error";
});

$router->run();
