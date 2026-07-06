<?php

declare(strict_types=1);

/**
 * Patrón Table Data Gateway sobre `users_data` + `users_login`.
 * Aquí vive también la seguridad de contraseñas: SIEMPRE se guardan
 * hasheadas con password_hash() y se comprueban con password_verify();
 * la contraseña en claro nunca sale de este archivo.
 */
class Usuarios
{
    /**
     * Registra un usuario. En el esquema real users_login.idUser es una FK a
     * users_data, así que primero insertamos los datos (obtenemos idUser) y
     * luego el login.
     */
    public static function registrar(
        string $nombre,
        string $apellidos,
        string $email,
        string $telefono,
        string $fechaNacimiento,
        string $usuario,
        string $password,
        string $rol = 'user',
        string $direccion = '',
        ?string $sexo = null
    ): array
    {
        $db = Database::connect();

        // Verificar que el usuario no existe
        $stmt = $db->prepare('SELECT idLogin FROM users_login WHERE usuario = ?');
        $stmt->bind_param('s', $usuario);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return ['success' => false, 'error' => 'El usuario ya existe'];
        }

        // Verificar que el email no existe
        $stmt = $db->prepare('SELECT idUser FROM users_data WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return ['success' => false, 'error' => 'El email ya está registrado'];
        }

        // Normalizar rol y sexo a los valores válidos del ENUM
        $rol = $rol === 'admin' ? 'admin' : 'user';
        $sexo = in_array($sexo, ['Hombre', 'Mujer'], true) ? $sexo : null;

        // 1) Insertar datos de perfil
        $stmt = $db->prepare(
            'INSERT INTO users_data (nombre, apellidos, email, telefono, fecha_nacimiento, direccion, sexo)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('sssssss', $nombre, $apellidos, $email, $telefono, $fechaNacimiento, $direccion, $sexo);
        if (!$stmt->execute()) {
            return ['success' => false, 'error' => 'Error al guardar datos'];
        }
        $idUser = $stmt->insert_id;

        // 2) Insertar login con ese idUser
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare(
            'INSERT INTO users_login (idUser, usuario, password, rol) VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('isss', $idUser, $usuario, $passwordHash, $rol);
        if (!$stmt->execute()) {
            // Revertir el perfil para no dejar datos huérfanos
            $del = $db->prepare('DELETE FROM users_data WHERE idUser = ?');
            $del->bind_param('i', $idUser);
            $del->execute();
            return ['success' => false, 'error' => 'Error al crear usuario'];
        }

        return ['success' => true, 'idUser' => $idUser];
    }

    /**
     * Lista todos los usuarios con sus datos de login y perfil (panel admin).
     */
    public static function getAll(): array
    {
        $db = Database::connect();
        $result = $db->query(
            'SELECT l.idUser, l.usuario, l.rol, d.nombre, d.apellidos, d.email
             FROM users_login l
             INNER JOIN users_data d ON l.idUser = d.idUser
             ORDER BY l.idUser ASC'
        );

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Lista usuarios con paginación (panel admin).
     */
    public static function getAllConTotal(int $pagina, int $porPagina): array
    {
        $db = Database::connect();
        $stmt = $db->prepare('SELECT COUNT(*) AS total FROM users_login');
        $stmt->execute();
        $total = (int) $stmt->get_result()->fetch_assoc()['total'];

        $offset = ($pagina - 1) * $porPagina;
        $stmt = $db->prepare(
            'SELECT l.idUser, l.usuario, l.rol, d.nombre, d.apellidos, d.email
             FROM users_login l
             INNER JOIN users_data d ON l.idUser = d.idUser
             ORDER BY l.idUser ASC
             LIMIT ? OFFSET ?'
        );
        $stmt->bind_param('ii', $porPagina, $offset);
        $stmt->execute();

        return [
            'total'   => $total,
            'usuarios' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC),
        ];
    }

    /**
     * Actualiza nombre, email, usuario y rol desde el panel admin.
     * Si $password no está vacío, también la restablece.
     */
    public static function actualizarComoAdmin(
        int $idUser,
        string $nombre,
        string $apellidos,
        string $email,
        string $telefono,
        string $fechaNacimiento,
        string $usuario,
        string $rol,
        string $password = ''
    ): bool
    {
        $db = Database::connect();
        $rol = $rol === 'admin' ? 'admin' : 'user';

        $stmt = $db->prepare(
            'UPDATE users_data SET nombre = ?, apellidos = ?, email = ?, telefono = ?, fecha_nacimiento = ?
             WHERE idUser = ?'
        );
        $stmt->bind_param('sssssi', $nombre, $apellidos, $email, $telefono, $fechaNacimiento, $idUser);
        if (!$stmt->execute()) {
            return false;
        }

        $stmt = $db->prepare('UPDATE users_login SET usuario = ?, rol = ? WHERE idUser = ?');
        $stmt->bind_param('ssi', $usuario, $rol, $idUser);
        if (!$stmt->execute()) {
            return false;
        }

        if ($password !== '') {
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare('UPDATE users_login SET password = ? WHERE idUser = ?');
            $stmt->bind_param('si', $passwordHash, $idUser);
            if (!$stmt->execute()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Elimina un usuario. Las FK ON DELETE CASCADE de users_login, noticias,
     * conciertos y asistencias se encargan del resto al borrar users_data.
     */
    public static function eliminar(int $idUser): bool
    {
        $db = Database::connect();
        $stmt = $db->prepare('DELETE FROM users_data WHERE idUser = ?');
        $stmt->bind_param('i', $idUser);

        return $stmt->execute();
    }

    /**
     * Actualiza el perfil del propio usuario.
     */
    public static function actualizar(
        int $idUser,
        string $nombre,
        string $apellidos,
        string $email,
        string $telefono = '',
        string $fechaNacimiento = '',
        string $direccion = '',
        ?string $sexo = null
    ): bool
    {
        $db = Database::connect();
        $sexo = in_array($sexo, ['Hombre', 'Mujer'], true) ? $sexo : null;

        $stmt = $db->prepare(
            'UPDATE users_data
             SET nombre = ?, apellidos = ?, email = ?, telefono = ?, fecha_nacimiento = ?, direccion = ?, sexo = ?
             WHERE idUser = ?'
        );
        $stmt->bind_param('sssssssi', $nombre, $apellidos, $email, $telefono, $fechaNacimiento, $direccion, $sexo, $idUser);

        return $stmt->execute();
    }

    /**
     * Cambia la contraseña del propio usuario, comprobando antes la actual.
     * Vale tanto para usuarios normales como para admins: cualquiera puede
     * cambiar su propia contraseña desde "Mi Perfil".
     */
    public static function cambiarPassword(int $idUser, string $passwordActual, string $passwordNueva): array
    {
        $db = Database::connect();

        $stmt = $db->prepare('SELECT password FROM users_login WHERE idUser = ?');
        $stmt->bind_param('i', $idUser);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();

        if (!$fila || !password_verify($passwordActual, $fila['password'])) {
            return ['success' => false, 'error' => 'La contraseña actual no es correcta'];
        }

        $passwordHash = password_hash($passwordNueva, PASSWORD_BCRYPT);
        $stmt = $db->prepare('UPDATE users_login SET password = ? WHERE idUser = ?');
        $stmt->bind_param('si', $passwordHash, $idUser);

        return ['success' => $stmt->execute()];
    }

    public static function obtener(int $idUser): ?array
    {
        $db = Database::connect();

        $stmt = $db->prepare(
            'SELECT d.*, l.usuario, l.rol
             FROM users_data d
             INNER JOIN users_login l ON d.idUser = l.idUser
             WHERE d.idUser = ?'
        );
        $stmt->bind_param('i', $idUser);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->num_rows === 1 ? $result->fetch_assoc() : null;
    }
}
