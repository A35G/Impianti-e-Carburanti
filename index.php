<?php
header('Content-Type: application/json; charset=utf-8');

$isDebug = true;

if ($isDebug) {
error_reporting(E_ALL);
ini_set("display_errors", 1);
}

require_once __DIR__ . '/vendor/autoload.php';

use App\System\Core;

require_once __DIR__ . '/app/Routes/Route.php';
