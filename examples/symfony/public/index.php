<?php

declare(strict_types=1);

require dirname(__DIR__, 3).'/vendor/autoload.php';

use LotteryToolkitExample\Kernel;
use Symfony\Component\HttpFoundation\Request;

$kernel = new Kernel('dev', true);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
