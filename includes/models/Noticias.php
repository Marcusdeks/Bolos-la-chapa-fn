<?php

declare(strict_types=1);

/**
 * Patrón Table Data Gateway sobre la tabla `noticias`
 * (misma idea que Conciertos: todo su SQL vive aquí).
 */
class Noticias
{
    public static function getAll(): array
    {
        $db = Database::connect();
        $result = $db->query(
            'SELECT n.idNoticia, n.titulo, n.imagen, n.texto, n.fecha,
                    u.nombre, u.apellidos
             FROM noticias n
             LEFT JOIN users_data u ON n.idUser = u.idUser
             ORDER BY n.fecha DESC'
        );

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public static function getAllConTotal(int $pagina, int $porPagina): array
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'SELECT COUNT(*) AS total FROM noticias'
        );
        $stmt->execute();
        $total = (int) $stmt->get_result()->fetch_assoc()['total'];

        $offset = ($pagina - 1) * $porPagina;
        $stmt = $db->prepare(
            'SELECT n.idNoticia, n.titulo, n.imagen, n.texto, n.fecha,
                    u.nombre, u.apellidos
             FROM noticias n
             LEFT JOIN users_data u ON n.idUser = u.idUser
             ORDER BY n.fecha DESC
             LIMIT ? OFFSET ?'
        );
        $stmt->bind_param('ii', $porPagina, $offset);
        $stmt->execute();

        return [
            'total'    => $total,
            'noticias' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC),
        ];
    }

    public static function getById(int $id): ?array
    {
        $db = Database::connect();
        $stmt = $db->prepare('SELECT * FROM noticias WHERE idNoticia = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public static function crear(int $idUser, string $titulo, string $texto, string $imagen): bool
    {
        $db = Database::connect();
        $fecha = date('Y-m-d');
        $stmt = $db->prepare(
            'INSERT INTO noticias (titulo, imagen, texto, fecha, idUser)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('ssssi', $titulo, $imagen, $texto, $fecha, $idUser);

        return $stmt->execute();
    }

    public static function actualizar(int $id, string $titulo, string $texto, string $imagen = ''): bool
    {
        $db = Database::connect();

        if ($imagen === '') {
            $stmt = $db->prepare(
                'UPDATE noticias SET titulo = ?, texto = ? WHERE idNoticia = ?'
            );
            $stmt->bind_param('ssi', $titulo, $texto, $id);
        } else {
            $stmt = $db->prepare(
                'UPDATE noticias SET titulo = ?, texto = ?, imagen = ? WHERE idNoticia = ?'
            );
            $stmt->bind_param('sssi', $titulo, $texto, $imagen, $id);
        }

        return $stmt->execute();
    }

    public static function eliminar(int $id): bool
    {
        $db = Database::connect();
        $stmt = $db->prepare('DELETE FROM noticias WHERE idNoticia = ?');
        $stmt->bind_param('i', $id);

        return $stmt->execute();
    }
}
