<?php

declare(strict_types=1);

use FoundryHerald\Config;
use FoundryHerald\Database;

define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . '/vendor/autoload.php';

Config::load(APP_ROOT);

$db = Database::connection();

use FoundryHerald\Services\KnowledgeLoader;

$knowledgeLoader = new KnowledgeLoader(
    APP_ROOT . '/knowledge'
);

$heraldContext = $knowledgeLoader->buildContext([
    'house-dainislaav.md',
    'voice-and-tone.md',
    'content-rules.md',
]);

use FoundryHerald\Services\ContentGenerator;

$generatedPost = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $generator = new ContentGenerator();

        $generatedPost = $generator->generate(
            $heraldContext,
            'Forge Reflection',
            'Starting over and becoming a beginner again'
        );
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

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

    <form method="post">
        <button type="submit">
            Generate Test Post
        </button>
    </form>

    <?php if ($error): ?>
        <div style="margin-top: 2rem; color: #ff8080;">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($generatedPost): ?>
        <div style="
            max-width: 700px;
            margin: 2rem auto;
            padding: 1.5rem;
            text-align: left;
            white-space: pre-wrap;
            background: #1a1a1a;
            border: 1px solid #333;
        "><?= htmlspecialchars(
            $generatedPost,
            ENT_QUOTES,
            'UTF-8'
        ) ?></div>
    <?php endif; ?>
</main>

</body>
</html>