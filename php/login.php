<?php

declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';

$mensaje = '';
$tipo_mensaje = ''; // 'error' o 'exito'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($usuario === '' || $password === '') {
        $mensaje = 'Completa usuario y contraseña';
        $tipo_mensaje = 'error';
    } else {
        $db = Database::connect();
        $stmt = $db->prepare('SELECT idUser, usuario, password, rol FROM users_login WHERE usuario = ?');
        $stmt->bind_param('s', $usuario);
        $stmt->execute();

        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $fila = $result->fetch_assoc();

            if (password_verify($password, $fila['password'])) {
                Auth::login($fila['usuario'], (string)$fila['idUser'], $fila['rol']);
                $mensaje = '¡Sesión iniciada! Redirigiendo al inicio...';
                $tipo_mensaje = 'exito';
                // Mostramos el mensaje de confirmación y redirigimos tras 1,5s
                header('refresh:1.5;url=../index.php');
            } else {
                $mensaje = 'Contraseña incorrecta';
                $tipo_mensaje = 'error';
            }
        } else {
            $mensaje = 'Usuario no encontrado';
            $tipo_mensaje = 'error';
        }
    }
}

$pagina = 'login';
?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="../estilos/registro.css">

<title>Login</title>

</head>

<body>

<header>
    <img class="logo" src="../imagenes/LogoBolosLaChapa.png" alt="Logo">
    <h2 class="tituloPrincipal">LOS MEJORES BOLOS PAL MEJOR PUBLICO</h2>
</header>

<?php
    $rutaBase = "../";
    include("partials/navBar.php");
?>

<main class="mainLogin">
<section class="contenedorFormulario contenedorFormularioLogin">

<h1 class="loginTitulo">Iniciar sesión</h1>
<p class="loginIntro">Accede a tu cuenta para gestionar noticias, conciertos y tu perfil de usuario.</p>

<?php if($mensaje != ""){ ?>

<div class="mensaje <?= $tipo_mensaje ?>">
<?php echo htmlspecialchars($mensaje); ?>
</div>

<?php } ?>

<form method="POST" class="formLogin">

<div class="formGrupo">
<label>Usuario</label>
<input type="text" name="usuario" required>
</div>

<div class="formGrupo">
<label>Contraseña</label>
<input type="password" name="password" required>
</div>

<button class="btnFormulario" type="submit">
Entrar
</button>

</form>

<p class="enlaceAcceso enlaceAccesoLogin">
¿No tienes cuenta?
<a href="registro.php">Regístrate</a>
</p>

</section>
</main>

<?php include("partials/footer.php"); ?>

</body>
</html>