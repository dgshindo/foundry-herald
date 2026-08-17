<?php

declare(strict_types=1);
use FoundryHerald\Config;
use FoundryHerald\Services\AuthService;
define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/vendor/autoload.php';
Config::load(APP_ROOT);
AuthService::logout();
header('Location: /login.php');
exit;
