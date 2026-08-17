<?php

declare(strict_types=1);
use FoundryHerald\Config;
use FoundryHerald\Services\AuthService;
define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/vendor/autoload.php';
Config::load(APP_ROOT);
AuthService::startSession();
if (AuthService::check()) { header('Location: /'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username=trim((string)($_POST['username']??'')); $password=(string)($_POST['password']??'');
    if (AuthService::attempt($username,$password)) { header('Location: /'); exit; }
    $error='Invalid username or password.';
}
function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Sign In | Foundry Herald</title>
<style>*{box-sizing:border-box}:root{color-scheme:dark;--background:#0d0d0d;--surface:#171717;--surface-secondary:#1f1f1f;--border:#343434;--text:#f3f3f3;--muted:#969292;--accent:#b56a2d;--accent-hover:#cf7c35;--danger:#ff8b8b}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:radial-gradient(circle at top,#1c1510 0,var(--background) 50%);color:var(--text);font-family:Arial,Helvetica,sans-serif}.login-card{width:min(420px,100%);padding:32px;background:var(--surface);border:1px solid var(--border);border-radius:8px}h1{margin:0 0 6px;text-align:center;letter-spacing:.04em}.subtitle{margin:0 0 28px;color:var(--muted);text-align:center}.field{display:flex;flex-direction:column;gap:7px;margin-bottom:18px}label{font-size:.85rem;font-weight:bold;color:#d4d1d1}input{width:100%;padding:11px 12px;background:var(--surface-secondary);color:var(--text);border:1px solid var(--border);border-radius:4px;font:inherit}input:focus{outline:none;border-color:var(--accent)}button{width:100%;padding:12px 20px;background:var(--accent);color:#fff;border:0;border-radius:4px;font-size:.9rem;font-weight:bold;cursor:pointer}button:hover{background:var(--accent-hover)}.error{margin-bottom:18px;padding:12px 14px;color:var(--danger);background:#251616;border:1px solid #5e2929;border-radius:5px}</style></head><body><main class="login-card"><h1>Foundry Herald</h1><p class="subtitle">Sign in to continue</p><?php if($error!==''): ?><div class="error"><?= e($error) ?></div><?php endif; ?><form method="post" action="/login.php"><div class="field"><label for="username">Username</label><input id="username" name="username" type="text" autocomplete="username" required autofocus></div><div class="field"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required></div><button type="submit">Sign In</button></form></main></body></html>
