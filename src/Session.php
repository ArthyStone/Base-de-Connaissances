<?php
declare(strict_types=1);

namespace Src;

class Session {
    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {

            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
//                'secure' => true, // n'hésitez pas à remettre ce paramètre si vous hébergez votre site en https (ou en localhost, c'est toléré)
                'httponly' => true,
                'samesite' => 'strict'
            ]);

            session_start();
        }
    }

    public static function set(string $key, mixed $value): void {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key): mixed {
        return $_SESSION[$key] ?? null;
    }

    public static function has(string $key): bool {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void {
        session_destroy();
    }
}