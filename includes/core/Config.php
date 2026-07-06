<?php

declare(strict_types=1);

/**
 * Configuración centralizada: un único sitio donde viven los parámetros
 * de conexión, leídos de variables de entorno con valores por defecto
 * para XAMPP. Ninguna otra clase conoce credenciales.
 */
class Config
{
    /**
     * Lee una variable de entorno de forma robusta.
     *
     * Bajo Apache la directiva variables_order no suele incluir "E", por lo
     * que $_ENV puede estar vacío. getenv() la lee siempre; usamos
     * $_ENV/$_SERVER como respaldo.
     */
    private static function env(string $clave, string $defecto): string
    {
        $valor = getenv($clave);
        if ($valor !== false && $valor !== '') {
            return $valor;
        }

        return $_ENV[$clave] ?? $_SERVER[$clave] ?? $defecto;
    }

    public static function getDbHost(): string
    {
        return self::env('DB_HOST', '127.0.0.1');
    }

    public static function getDbUser(): string
    {
        return self::env('DB_USER', 'root');
    }

    public static function getDbPassword(): string
    {
        return self::env('DB_PASSWORD', '');
    }

    public static function getDbName(): string
    {
        return self::env('DB_NAME', 'bolos_la_chapa');
    }

    public static function getDbPort(): int
    {
        return (int) self::env('DB_PORT', '3306');
    }
}
