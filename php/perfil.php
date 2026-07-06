<?php

declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';

Auth::requireLogin();

$idUser = Auth::getUser()['idUser'];
$mensaje = '';
$tipo_mensaje = '';
$mensajePassword = '';
$tipoMensajePassword = '';

// Obtener datos del usuario
$datosUsuario = Usuarios::obtener($idUser);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_password'])) {
    $passwordActual = $_POST['password_actual'] ?? '';
    $passwordNueva = $_POST['password_nueva'] ?? '';
    $passwordConfirmar = $_POST['password_confirmar'] ?? '';

    if (!$passwordActual || !$passwordNueva || !$passwordConfirmar) {
        $mensajePassword = 'Completa todos los campos';
        $tipoMensajePassword = 'error';
    } elseif ($passwordNueva !== $passwordConfirmar) {
        $mensajePassword = 'Las contraseñas nuevas no coinciden';
        $tipoMensajePassword = 'error';
    } elseif (strlen($passwordNueva) < 6) {
        $mensajePassword = 'La nueva contraseña debe tener al menos 6 caracteres';
        $tipoMensajePassword = 'error';
    } else {
        $resultado = Usuarios::cambiarPassword($idUser, $passwordActual, $passwordNueva);
        if ($resultado['success']) {
            $mensajePassword = 'Contraseña actualizada correctamente';
            $tipoMensajePassword = 'exito';
        } else {
            $mensajePassword = $resultado['error'] ?? 'Error al actualizar la contraseña';
            $tipoMensajePassword = 'error';
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $sexo = trim($_POST['sexo'] ?? '');

    if (!$nombre || !$apellidos || !$email || !$telefono || !$fecha_nacimiento) {
        $mensaje = 'Faltan campos obligatorios';
        $tipo_mensaje = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = 'Email inválido';
        $tipo_mensaje = 'error';
    } else {
        if (Usuarios::actualizar($idUser, $nombre, $apellidos, $email, $telefono, $fecha_nacimiento, $direccion, $sexo !== '' ? $sexo : null)) {
            $mensaje = 'Perfil actualizado correctamente';
            $tipo_mensaje = 'exito';
            // Recargar datos
            $datosUsuario = Usuarios::obtener($idUser);
        } else {
            $mensaje = 'Error al actualizar perfil';
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
    <title>Mi Perfil - Bolos La Chapa</title>
    <link rel="stylesheet" href="../estilos/perfil.css">
</head>
<body>

<header>
    <img class="logo" src="../imagenes/LogoBolosLaChapa.png" alt="Logo">
    <h2 class="tituloPrincipal">LOS MEJORES BOLOS PAL MEJOR PUBLICO</h2>
</header>

<?php
    $pagina = 'perfil';
    $rutaBase = '../';
    include('partials/navBar.php');
?>

<main class="mainPerfil">

    <div class="contenedorPerfil">
        <h1>Mi Perfil</h1>

        <?php if ($mensaje): ?>
            <div class="mensaje <?= $tipo_mensaje ?>">
                <?= esc($mensaje) ?>
            </div>
        <?php endif; ?>

        <?php if ($datosUsuario): ?>
            <div class="datosPerfil">
                <p><strong>Usuario:</strong> <?= esc($datosUsuario['usuario']) ?></p>
                <p><strong>Rol:</strong> <?= esc($datosUsuario['rol']) ?></p>
            </div>

            <form method="POST" class="formularioPerfil">
                <div class="campo">
                    <label for="nombre">Nombre *</label>
                    <input 
                        type="text" 
                        id="nombre" 
                        name="nombre" 
                        value="<?= esc($datosUsuario['nombre'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="campo">
                    <label for="apellidos">Apellidos *</label>
                    <input
                        type="text"
                        id="apellidos"
                        name="apellidos"
                        value="<?= esc($datosUsuario['apellidos'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="campo">
                    <label for="email">Email *</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= esc($datosUsuario['email'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="campo">
                    <label for="telefono">Teléfono *</label>
                    <input
                        type="tel"
                        id="telefono"
                        name="telefono"
                        value="<?= esc($datosUsuario['telefono'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="campo">
                    <label for="fecha_nacimiento">Fecha de nacimiento *</label>
                    <input
                        type="date"
                        id="fecha_nacimiento"
                        name="fecha_nacimiento"
                        value="<?= esc($datosUsuario['fecha_nacimiento'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="campo ancho">
                    <label for="direccion">Dirección</label>
                    <input
                        type="text"
                        id="direccion"
                        name="direccion"
                        value="<?= esc($datosUsuario['direccion'] ?? '') ?>"
                    >
                </div>

                <div class="campo">
                    <label for="sexo">Sexo</label>
                    <select id="sexo" name="sexo">
                        <option value="">Prefiero no decirlo</option>
                        <option value="Hombre" <?= (($datosUsuario['sexo'] ?? '') === 'Hombre') ? 'selected' : '' ?>>Hombre</option>
                        <option value="Mujer" <?= (($datosUsuario['sexo'] ?? '') === 'Mujer') ? 'selected' : '' ?>>Mujer</option>
                    </select>
                </div>

                <button type="submit" class="btnFormulario ancho">Guardar Cambios</button>
            </form>

            <h2 class="subtituloPerfil">Cambiar contraseña</h2>

            <?php if ($mensajePassword): ?>
                <div class="mensaje <?= $tipoMensajePassword ?>">
                    <?= esc($mensajePassword) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="formularioPerfil">
                <div class="campo ancho">
                    <label for="password_actual">Contraseña actual *</label>
                    <input
                        type="password"
                        id="password_actual"
                        name="password_actual"
                        required
                    >
                </div>

                <div class="campo">
                    <label for="password_nueva">Nueva contraseña *</label>
                    <input
                        type="password"
                        id="password_nueva"
                        name="password_nueva"
                        minlength="6"
                        required
                    >
                </div>

                <div class="campo">
                    <label for="password_confirmar">Confirmar nueva contraseña *</label>
                    <input
                        type="password"
                        id="password_confirmar"
                        name="password_confirmar"
                        minlength="6"
                        required
                    >
                </div>

                <button type="submit" name="cambiar_password" class="btnFormulario ancho">Cambiar contraseña</button>
            </form>
        <?php else: ?>
            <p>Error al cargar datos del perfil</p>
        <?php endif; ?>
    </div>

</main>

<?php include("partials/footer.php"); ?>

</body>
</html>
