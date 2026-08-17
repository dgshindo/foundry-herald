# Foundry Herald Authentication v1

Upload preserving folders to `/home/theslxfz/public_html/herald/`. Do not replace `.env`.

Tests:
1. Logged out `/` redirects to `/login.php`.
2. Existing database user can sign in.
3. Herald works normally after login.
4. In a fresh private/incognito session, `/api/publishing-destinations.php` returns HTTP 401 JSON.
5. `/logout.php` ends the session and returns to login.

Important: this package protects every API endpoint you supplied. If `public/api/` contains additional PHP endpoints, add `use FoundryHerald\Services\AuthService;` and call `AuthService::requireApi();` after `Config::load(APP_ROOT);`.
