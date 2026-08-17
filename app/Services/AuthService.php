<?php

declare(strict_types=1);

namespace FoundryHerald\Services;

use FoundryHerald\Database;
use PDO;

final class AuthService
{
    private const SESSION_USER_KEY = 'herald_user';

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) { return; }
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_name('foundry_herald_session');
        session_set_cookie_params([
            'lifetime' => 0, 'path' => '/', 'domain' => '',
            'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function attempt(string $username, string $password): bool
    {
        self::startSession();
        $username = trim($username);
        if ($username === '' || $password === '') { return false; }
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id, username, password_hash, display_name, is_active FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($user) || !(bool)$user['is_active'] || !password_verify($password, (string)$user['password_hash'])) { return false; }
        session_regenerate_id(true);
        $_SESSION[self::SESSION_USER_KEY] = [
            'id' => (int)$user['id'],
            'username' => (string)$user['username'],
            'display_name' => (string)$user['display_name'],
        ];
        return true;
    }

    public static function check(): bool
    {
        self::startSession();
        return isset($_SESSION[self::SESSION_USER_KEY]['id']);
    }

    public static function user(): ?array
    {
        self::startSession();
        $user = $_SESSION[self::SESSION_USER_KEY] ?? null;
        return is_array($user) ? $user : null;
    }

    public static function requireWeb(): void
    {
        if (self::check()) { return; }
        header('Location: /login.php'); exit;
    }

    public static function requireApi(): void
    {
        if (self::check()) { return; }
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode(['success'=>false,'error'=>'Authentication required.'], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function logout(): void
    {
        self::startSession(); $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p=session_get_cookie_params();
            setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], (bool)$p['secure'], (bool)$p['httponly']);
        }
        session_destroy();
    }
}
