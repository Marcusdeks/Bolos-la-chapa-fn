<?php

declare(strict_types=1);

/**
 * Patrón Singleton: la conexión mysqli se crea UNA sola vez por petición
 * y todas las clases comparten la misma (se guarda en $connection, que es
 * estática). Sin esto, cada consulta abriría una conexión nueva a MySQL.
 */
class Database
{
    private static ?mysqli $connection = null;

    public static function connect(): mysqli
    {
        if (self::$connection instanceof mysqli) {
            return self::$connection;
        }

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            self::$connection = mysqli_connect(
                Config::getDbHost(),
                Config::getDbUser(),
                Config::getDbPassword(),
                Config::getDbName(),
                Config::getDbPort()
            );

            if (!self::$connection) {
                throw new Exception('Error en la conexión a la base de datos');
            }

            mysqli_set_charset(self::$connection, 'utf8mb4');
        } catch (Exception $e) {
            die('Error de conexión: ' . htmlspecialchars($e->getMessage()));
        }

        return self::$connection;
    }

    public static function prepare(string $sql): mysqli_stmt
    {
        $conn = self::connect();
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception('Error en prepared statement: ' . htmlspecialchars($conn->error));
        }

        return $stmt;
    }

    public static function close(): void
    {
        if (self::$connection instanceof mysqli) {
            self::$connection->close();
            self::$connection = null;
        }
    }
}
