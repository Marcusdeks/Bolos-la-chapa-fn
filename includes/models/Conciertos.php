<?php

declare(strict_types=1);

/**
 * Patrón Table Data Gateway: esta clase es la ÚNICA puerta de entrada a
 * las tablas `conciertos` y `asistencias`. Todo el SQL de conciertos vive
 * aquí; las páginas solo llaman a métodos con nombres de negocio
 * (crear, apuntarse, buscarConTotal...) y reciben arrays ya listos.
 */
class Conciertos
{
    public static function getAll(): array
    {
        $db = Database::connect();
        $result = $db->query(
            'SELECT c.idConcierto, c.nombre_grupo, c.estilo_musica, c.fecha_concierto, c.lugar,
                    COALESCE(l.usuario, CONCAT(d.nombre, " ", d.apellidos)) AS usuario
             FROM conciertos c
             INNER JOIN users_data d ON c.idUser = d.idUser
             LEFT JOIN users_login l ON c.idUser = l.idUser
             ORDER BY c.fecha_concierto ASC'
        );

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public static function getById(int $idConcierto): ?array
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'SELECT idConcierto, idUser, nombre_grupo, estilo_musica, fecha_concierto, lugar, descripcion
             FROM conciertos WHERE idConcierto = ?'
        );
        $stmt->bind_param('i', $idConcierto);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public static function getMios(int $idUser): array
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'SELECT c.idConcierto, c.nombre_grupo, c.estilo_musica, c.fecha_concierto, c.lugar, c.descripcion
             FROM conciertos c
             WHERE c.idUser = ?
             ORDER BY c.fecha_concierto ASC'
        );
        $stmt->bind_param('i', $idUser);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Devuelve el total de resultados y una página de conciertos.
     *
     * Regla de visibilidad: si $soloVisiblesPara trae un idUser, cada usuario
     * ve los conciertos oficiales (creados por un admin) más los suyos propios.
     * Con null (panel de administración) se devuelven todos sin filtrar.
     */
    public static function buscarConTotal(
        string $grupo,
        string $estilo,
        string $lugar,
        int $pagina,
        int $porPagina,
        ?int $soloVisiblesPara = null
    ): array {
        $db = Database::connect();
        $like = static fn(string $v): string => '%' . $v . '%';
        $g = $like($grupo);
        $e = $like($estilo);
        $l = $like($lugar);

        $filtroVisibilidad = $soloVisiblesPara !== null
            ? " AND (l.rol = 'admin' OR c.idUser = ?)"
            : '';

        $stmt = $db->prepare(
            'SELECT COUNT(*) AS total
             FROM conciertos c
             LEFT JOIN users_login l ON c.idUser = l.idUser
             WHERE c.nombre_grupo LIKE ?
               AND c.estilo_musica LIKE ?
               AND c.lugar LIKE ?' . $filtroVisibilidad
        );
        if ($soloVisiblesPara !== null) {
            $stmt->bind_param('sssi', $g, $e, $l, $soloVisiblesPara);
        } else {
            $stmt->bind_param('sss', $g, $e, $l);
        }
        $stmt->execute();
        $total = (int) $stmt->get_result()->fetch_assoc()['total'];

        $offset = ($pagina - 1) * $porPagina;
        $stmt = $db->prepare(
            'SELECT c.idConcierto, c.idUser, c.nombre_grupo, c.estilo_musica, c.fecha_concierto, c.lugar, c.descripcion,
                    COALESCE(l.usuario, CONCAT(d.nombre, " ", d.apellidos)) AS usuario
             FROM conciertos c
             INNER JOIN users_data d ON c.idUser = d.idUser
             LEFT JOIN users_login l ON c.idUser = l.idUser
             WHERE c.nombre_grupo LIKE ?
               AND c.estilo_musica LIKE ?
               AND c.lugar LIKE ?' . $filtroVisibilidad . '
             ORDER BY c.fecha_concierto ASC
             LIMIT ? OFFSET ?'
        );
        if ($soloVisiblesPara !== null) {
            $stmt->bind_param('sssiii', $g, $e, $l, $soloVisiblesPara, $porPagina, $offset);
        } else {
            $stmt->bind_param('sssii', $g, $e, $l, $porPagina, $offset);
        }
        $stmt->execute();

        return [
            'total'      => $total,
            'conciertos' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC),
        ];
    }

    /**
     * Busca conciertos filtrando por grupo, estilo y/o lugar (búsqueda parcial).
     * Si todos los filtros están vacíos, devuelve todos.
     */
    public static function buscar(string $grupo, string $estilo, string $lugar): array
    {
        $db = Database::connect();
        $like = static fn(string $v): string => '%' . $v . '%';

        $stmt = $db->prepare(
            'SELECT c.idConcierto, c.nombre_grupo, c.estilo_musica, c.fecha_concierto, c.lugar,
                    COALESCE(l.usuario, CONCAT(d.nombre, " ", d.apellidos)) AS usuario
             FROM conciertos c
             INNER JOIN users_data d ON c.idUser = d.idUser
             LEFT JOIN users_login l ON c.idUser = l.idUser
             WHERE c.nombre_grupo LIKE ?
               AND c.estilo_musica LIKE ?
               AND c.lugar LIKE ?
             ORDER BY c.fecha_concierto ASC'
        );
        $g = $like($grupo);
        $e = $like($estilo);
        $l = $like($lugar);
        $stmt->bind_param('sss', $g, $e, $l);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Agenda del usuario: conciertos a los que se ha apuntado.
     */
    public static function getAgenda(int $idUser): array
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'SELECT c.idConcierto, c.nombre_grupo, c.estilo_musica, c.fecha_concierto, c.lugar,
                    COALESCE(l.usuario, CONCAT(d.nombre, " ", d.apellidos)) AS usuario
             FROM asistencias a
             INNER JOIN conciertos c ON a.idConcierto = c.idConcierto
             INNER JOIN users_data d ON c.idUser = d.idUser
             LEFT JOIN users_login l ON c.idUser = l.idUser
             WHERE a.idUser = ?
             ORDER BY c.fecha_concierto ASC'
        );
        $stmt->bind_param('i', $idUser);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Devuelve los ids de conciertos a los que el usuario está apuntado,
     * para marcar el estado de los botones en la lista.
     */
    public static function getIdsAgenda(int $idUser): array
    {
        $db = Database::connect();
        $stmt = $db->prepare('SELECT idConcierto FROM asistencias WHERE idUser = ?');
        $stmt->bind_param('i', $idUser);
        $stmt->execute();

        $ids = [];
        $result = $stmt->get_result();
        while ($fila = $result->fetch_assoc()) {
            $ids[] = (int) $fila['idConcierto'];
        }

        return $ids;
    }

    public static function apuntarse(int $idUser, int $idConcierto): bool
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'INSERT IGNORE INTO asistencias (idUser, idConcierto) VALUES (?, ?)'
        );
        $stmt->bind_param('ii', $idUser, $idConcierto);

        return $stmt->execute();
    }

    public static function quitarse(int $idUser, int $idConcierto): bool
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'DELETE FROM asistencias WHERE idUser = ? AND idConcierto = ?'
        );
        $stmt->bind_param('ii', $idUser, $idConcierto);

        return $stmt->execute();
    }

    public static function crear(int $idUser, string $grupo, string $estilo, string $fecha, string $lugar, string $descripcion = ''): bool
    {
        $db = Database::connect();
        // Guardamos NULL en vez de cadena vacía cuando no hay descripción
        $descripcion = $descripcion !== '' ? $descripcion : null;
        $stmt = $db->prepare(
            'INSERT INTO conciertos (idUser, nombre_grupo, estilo_musica, fecha_concierto, lugar, descripcion)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('isssss', $idUser, $grupo, $estilo, $fecha, $lugar, $descripcion);

        return $stmt->execute();
    }

    public static function actualizar(int $idConcierto, string $grupo, string $estilo, string $fecha, string $lugar, string $descripcion = ''): bool
    {
        $db = Database::connect();
        $descripcion = $descripcion !== '' ? $descripcion : null;
        $stmt = $db->prepare(
            'UPDATE conciertos
             SET nombre_grupo = ?, estilo_musica = ?, fecha_concierto = ?, lugar = ?, descripcion = ?
             WHERE idConcierto = ?'
        );
        $stmt->bind_param('sssssi', $grupo, $estilo, $fecha, $lugar, $descripcion, $idConcierto);

        return $stmt->execute();
    }

    public static function eliminar(int $idConcierto): bool
    {
        $db = Database::connect();
        $stmt = $db->prepare('DELETE FROM conciertos WHERE idConcierto = ?');
        $stmt->bind_param('i', $idConcierto);

        return $stmt->execute();
    }
}
