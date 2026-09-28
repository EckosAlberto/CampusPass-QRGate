<?php

@unlink(__DIR__.'/../bootstrap/cache/routes-v7.php');
@unlink(__DIR__.'/../bootstrap/cache/config.php');
@unlink(__DIR__.'/../bootstrap/cache/packages.php');

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));


if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}


require __DIR__.'/../vendor/autoload.php';


/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
