<?php

declare(strict_types=1);

/**
 * Patrón Facade (fachada): el resto del código nunca toca $_SESSION
 * directamente, siempre pasa por aquí. Si mañana cambias cómo guardas
 * la sesión (otra clave, tokens, etc.), solo tocas este archivo.
 *
 * requireLogin()/requireAdmin() son "guardias": se llaman en la primera
 * línea de cada página protegida y expulsan a quien no deba estar.
 */
class Auth
{
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['usuario']) && !empty($_SESSION['usuario']);
    }

    public static function isAdmin(): bool
    {
        return self::isLoggedIn() && ($_SESSION['rol'] ?? '') === 'admin';
    }

    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/php/login.php');
            exit;
        }
    }

    public static function requireAdmin(): void
    {
        if (!self::isAdmin()) {
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }
    }

    public static function getUser(): ?array
    {
        if (!self::isLoggedIn()) {
            return null;
        }

        return [
            'usuario' => $_SESSION['usuario'],
            'rol' => $_SESSION['rol'] ?? 'user',
            'idUser' => (int) ($_SESSION['idUser'] ?? 0),
        ];
    }

    public static function login(string $usuario, string $idUser, string $rol): void
    {
        $_SESSION['usuario'] = $usuario;
        $_SESSION['idUser'] = $idUser;
        $_SESSION['rol'] = $rol;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }
}
