<?php

declare(strict_types=1);

use FoundryHerald\Config;
use FoundryHerald\Database;

define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . '/vendor/autoload.php';

Config::load(APP_ROOT);

$db = Database::connection();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Foundry Herald</title>

    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #111;
            color: #eee;
            font-family: Arial, sans-serif;
        }

        main {
            text-align: center;
        }

        h1 {
            margin-bottom: .25rem;
            font-size: 2.5rem;
        }

        p {
            color: #999;
        }
    </style>
</head>

<body>

<main>
    <h1>Foundry Herald</h1>
    <p>House Dainislaav Content Agent</p>
</main>

</body>
</html>