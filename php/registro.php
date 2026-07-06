<?php

declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';

$mensaje = '';
$tipo_mensaje = ''; // 'error' o 'exito'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmar_password = $_POST['confirmar_password'] ?? '';
    $telefono = trim($_POST['telefono'] ?? '');
    $fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $sexo = trim($_POST['sexo'] ?? '');

    // Validaciones
    if (!$nombre || !$apellidos || !$email || !$usuario || !$password || !$telefono || !$fecha_nacimiento) {
        $mensaje = 'Faltan campos obligatorios';
        $tipo_mensaje = 'error';
    } elseif ($password !== $confirmar_password) {
        $mensaje = 'Las contraseñas no coinciden';
        $tipo_mensaje = 'error';
    } elseif (strlen($password) < 6) {
        $mensaje = 'La contraseña debe tener al menos 6 caracteres';
        $tipo_mensaje = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = 'Email inválido';
        $tipo_mensaje = 'error';
    } else {
        // Intentar registro
        $resultado = Usuarios::registrar(
            $nombre,
            $apellidos,
            $email,
            $telefono,
            $fecha_nacimiento,
            $usuario,
            $password,
            'user',
            $direccion,
            $sexo !== '' ? $sexo : null
        );

        if ($resultado['success']) {
            $mensaje = '¡Registro exitoso! Ahora puedes iniciar sesión.';
            $tipo_mensaje = 'exito';
            // Redirigir al login después de 2 segundos
            header('refresh:2;url=login.php');
        } else {
            $mensaje = $resultado['error'] ?? 'Error al registrar';
            $tipo_mensaje = 'error';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Bolos La Chapa</title>
    <link rel="stylesheet" href="../estilos/registro.css">
</head>
<body>

<header>
    <img class="logo" src="../imagenes/LogoBolosLaChapa.png" alt="Logo">
    <h2 class="tituloPrincipal">LOS MEJORES BOLOS PAL MEJOR PUBLICO</h2>
</header>

<?php
    $pagina = 'registro';
    $rutaBase = '../';
    include('partials/navBar.php');
?>

<main class="mainRegistro">

    <div class="contenedorFormulario">
        <h1 class="registroTitulo">Crear Cuenta</h1>

        <?php if ($mensaje): ?>
            <div class="mensaje <?= $tipo_mensaje ?>">
                <?= esc($mensaje) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="formRegistro">
            <div class="formGrupo">
                <label for="nombre">Nombre *</label>
                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    value="<?= esc($_POST['nombre'] ?? '') ?>"
                    required
                >
            </div>

            <div class="formGrupo">
                <label for="apellidos">Apellidos *</label>
                <input
                    type="text"
                    id="apellidos"
                    name="apellidos"
                    value="<?= esc($_POST['apellidos'] ?? '') ?>"
                    required
                >
            </div>

            <div class="formGrupo">
                <label for="email">Email *</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= esc($_POST['email'] ?? '') ?>"
                    required
                >
            </div>

            <div class="formGrupo">
                <label for="fecha_nacimiento">Fecha de nacimiento *</label>
                <input
                    type="date"
                    id="fecha_nacimiento"
                    name="fecha_nacimiento"
                    value="<?= esc($_POST['fecha_nacimiento'] ?? '') ?>"
                    required
                >
            </div>

            <div class="formGrupo">
                <label for="usuario">Usuario *</label>
                <input
                    type="text"
                    id="usuario"
                    name="usuario"
                    value="<?= esc($_POST['usuario'] ?? '') ?>"
                    required
                >
            </div>

            <div class="formGrupo">
                <label for="password">Contraseña *</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                >
            </div>

            <div class="formGrupo">
                <label for="confirmar_password">Confirmar Contraseña *</label>
                <input
                    type="password"
                    id="confirmar_password"
                    name="confirmar_password"
                    required
                >
            </div>

            <div class="formGrupo">
                <label for="telefono">Teléfono *</label>
                <input
                    type="tel"
                    id="telefono"
                    name="telefono"
                    value="<?= esc($_POST['telefono'] ?? '') ?>"
                    required
                >
            </div>

            <div class="formGrupo">
                <label for="direccion">Dirección</label>
                <input
                    type="text"
                    id="direccion"
                    name="direccion"
                    value="<?= esc($_POST['direccion'] ?? '') ?>"
                >
            </div>

            <div class="formGrupo">
                <label for="sexo">Sexo</label>
                <select id="sexo" name="sexo">
                    <option value="">Prefiero no decirlo</option>
                    <option value="Hombre" <?= (($_POST['sexo'] ?? '') === 'Hombre') ? 'selected' : '' ?>>Hombre</option>
                    <option value="Mujer" <?= (($_POST['sexo'] ?? '') === 'Mujer') ? 'selected' : '' ?>>Mujer</option>
                    <option value="Otro" <?= (($_POST['sexo'] ?? '') === 'Otro') ? 'selected' : '' ?>>Otro</option>
                </select>
            </div>

            <button type="submit" class="btnFormulario">Registrarse</button>
        </form>

        <p class="enlaceAcceso">
            ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
        </p>
    </div>

</main>

<?php include("partials/footer.php"); ?>

</body>
</html>
