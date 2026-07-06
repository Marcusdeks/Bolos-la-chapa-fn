<?php

declare(strict_types=1);

require __DIR__ . '/../../includes/bootstrap.php';

Auth::requireAdmin();

$mensaje = "";
$usuarioEditar = null;

if (isset($_POST['crear'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $fechaNacimiento = trim($_POST['fecha_nacimiento'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol = $_POST['rol'] ?? 'user';

    if (!$nombre || !$apellidos || !$email || !$telefono || !$fechaNacimiento || !$usuario || !$password) {
        $mensaje = "Faltan campos obligatorios";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "Email inválido";
    } else {
        $resultado = Usuarios::registrar($nombre, $apellidos, $email, $telefono, $fechaNacimiento, $usuario, $password, $rol);
        $mensaje = $resultado['success'] ? "Usuario creado" : ($resultado['error'] ?? "Error al crear usuario");
    }
}

if (isset($_POST['guardar'])) {
    $idEditar = (int) ($_POST['idUser'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $fechaNacimiento = trim($_POST['fecha_nacimiento'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $rol = $_POST['rol'] ?? 'user';
    $password = trim($_POST['password'] ?? '');

    if ($idEditar > 0 && $nombre && $apellidos && $email && $telefono && $fechaNacimiento && $usuario) {
        Usuarios::actualizarComoAdmin($idEditar, $nombre, $apellidos, $email, $telefono, $fechaNacimiento, $usuario, $rol, $password);
        $mensaje = "Usuario actualizado";
    } else {
        $mensaje = "Faltan campos obligatorios";
    }
}

if (isset($_GET['borrar'])) {
    Usuarios::eliminar((int) $_GET['borrar']);
    redirect('usuarioAdmin.php');
}

if (isset($_GET['editar'])) {
    $usuarioEditar = Usuarios::obtener((int) $_GET['editar']);
}

$porPagina = 8;
$paginaActual = max(1, (int) ($_GET['pag'] ?? 1));

$resultado = Usuarios::getAllConTotal($paginaActual, $porPagina);
$usuarios = $resultado['usuarios'];
$totalUsuarios = $resultado['total'];
$totalPaginas = max(1, (int) ceil($totalUsuarios / $porPagina));
$paginaActual = min($paginaActual, $totalPaginas);

function urlPaginaU(int $p): string
{
    return 'usuarioAdmin.php?pag=' . $p;
}

$pagina = "usuariosAdmin";
?>

<!DOCTYPE html>
<html lang="es">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../../estilos/admin.css">
<title>Usuarios Admin</title>
</head>

<body>

<header>
<img class="logo" src="../../imagenes/LogoBolosLaChapa.png" alt="Logo">
<h2 class="tituloPrincipal">LOS MEJORES BOLOS PAL MEJOR PUBLICO</h2>
</header>

<?php
    $rutaBase = "../../";
    include("../partials/navBar.php");
?>

<main class="mainAdmin">

<div class="panelAdmin panelAdminTabla">

<div class="encabezadoNoticiasAdmin">
    <h2 class="h2Main">Usuarios registrados</h2>
    <button type="button" class="btnCrearModal" id="btnAbrirModal">+ Crear usuario</button>
</div>

<?php if ($totalUsuarios > 0): ?>
    <p class="infoPaginacion">
        <?php
            $desde = ($paginaActual - 1) * $porPagina + 1;
            $hasta = min($paginaActual * $porPagina, $totalUsuarios);
            echo "Mostrando {$desde}–{$hasta} de {$totalUsuarios} usuarios";
        ?>
    </p>
<?php endif; ?>

<div class="tablaWrap">
<table class="tablaConciertos">

<tr>
<th>ID</th>
<th>Nombre</th>
<th>Usuario</th>
<th>Rol</th>
<th>Acciones</th>
</tr>

<?php foreach($usuarios as $fila){ ?>

<tr>
<td><?php echo (int) $fila['idUser']; ?></td>
<td><?php echo htmlspecialchars($fila['nombre'] ?? ''); ?></td>
<td><?php echo htmlspecialchars($fila['usuario']); ?></td>
<td><?php echo htmlspecialchars($fila['rol']); ?></td>

<td class="tablaAdminAccion">
<a href="?editar=<?php echo (int) $fila['idUser']; ?>">Editar</a>
<a href="?borrar=<?php echo (int) $fila['idUser']; ?>">Borrar</a>
</td>

</tr>

<?php } ?>

</table>

</div>

<?php if ($totalPaginas > 1): ?>
    <nav class="paginacion" aria-label="Paginación de usuarios">
        <?php if ($paginaActual > 1): ?>
            <a href="<?= esc(urlPaginaU($paginaActual - 1)) ?>" class="btnPag btnPagNav">← Anterior</a>
        <?php else: ?>
            <span class="btnPag btnPagNav desactivado">← Anterior</span>
        <?php endif; ?>

        <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
            <?php if ($p === $paginaActual): ?>
                <span class="btnPag activo"><?= $p ?></span>
            <?php else: ?>
                <a href="<?= esc(urlPaginaU($p)) ?>" class="btnPag"><?= $p ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($paginaActual < $totalPaginas): ?>
            <a href="<?= esc(urlPaginaU($paginaActual + 1)) ?>" class="btnPag btnPagNav">Siguiente →</a>
        <?php else: ?>
            <span class="btnPag btnPagNav desactivado">Siguiente →</span>
        <?php endif; ?>
    </nav>
<?php endif; ?>

</div>

</main>

<!-- Modal -->
<div id="modalFormulario" class="modal">
    <div class="modalContenido">
        <div class="modalEncabezado">
            <h2><?php echo $usuarioEditar ? 'Editar usuario' : 'Crear usuario'; ?></h2>
            <button type="button" class="btnCerrarModal" id="btnCerrarModal">✕</button>
        </div>

        <div class="modalBody">
            <?php if($mensaje !== ""){ ?>
            <p class="mensajeOk"><?php echo htmlspecialchars($mensaje); ?></p>
            <?php } ?>

            <form method="POST" class="formAdmin">

            <?php if($usuarioEditar){ ?>
            <input type="hidden" name="idUser" value="<?php echo (int) $usuarioEditar['idUser']; ?>">
            <?php } ?>

            <div class="formGrupo">
            <label>Nombre</label>
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($usuarioEditar['nombre'] ?? ''); ?>" required>
            </div>

            <div class="formGrupo">
            <label>Apellidos</label>
            <input type="text" name="apellidos" value="<?php echo htmlspecialchars($usuarioEditar['apellidos'] ?? ''); ?>" required>
            </div>

            <div class="formGrupo">
            <label>Email</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($usuarioEditar['email'] ?? ''); ?>" required>
            </div>

            <div class="formGrupo">
            <label>Teléfono</label>
            <input type="tel" name="telefono" value="<?php echo htmlspecialchars($usuarioEditar['telefono'] ?? ''); ?>" required>
            </div>

            <div class="formGrupo">
            <label>Fecha de nacimiento</label>
            <input type="date" name="fecha_nacimiento" value="<?php echo htmlspecialchars($usuarioEditar['fecha_nacimiento'] ?? ''); ?>" required>
            </div>

            <div class="formGrupo">
            <label>Usuario</label>
            <input type="text" name="usuario" value="<?php echo htmlspecialchars($usuarioEditar['usuario'] ?? ''); ?>" required>
            </div>

            <div class="formGrupo">
            <label>Password</label>
            <input type="password" name="password" <?php echo $usuarioEditar ? '' : 'required'; ?>>
            </div>

            <div class="formGrupo">
            <label>Rol</label>
            <select name="rol">
            <option value="user" <?php echo (($usuarioEditar['rol'] ?? '') === 'user') ? 'selected' : ''; ?>>Usuario</option>
            <option value="admin" <?php echo (($usuarioEditar['rol'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin</option>
            </select>
            </div>

            <div class="modalBotones">
                <button class="btnFormulario" type="submit" name="<?php echo $usuarioEditar ? 'guardar' : 'crear'; ?>">
                <?php echo $usuarioEditar ? 'Guardar' : 'Crear'; ?>
                </button>
                <button type="button" class="btnCancelar" id="btnCancelarModal">Cancelar</button>
            </div>

            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modalFormulario');
    const btnAbrir = document.getElementById('btnAbrirModal');
    const btnCerrar = document.getElementById('btnCerrarModal');
    const btnCancelar = document.getElementById('btnCancelarModal');

    function abrirModal() {
        modal.classList.add('activo');
        document.body.style.overflow = 'hidden';
    }

    function cerrarModal() {
        modal.classList.remove('activo');
        document.body.style.overflow = 'auto';
    }

    if (btnAbrir) {
        btnAbrir.addEventListener('click', abrirModal);
    }

    if (btnCerrar) {
        btnCerrar.addEventListener('click', cerrarModal);
    }

    if (btnCancelar) {
        btnCancelar.addEventListener('click', cerrarModal);
    }

    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            cerrarModal();
        }
    });

    <?php if ($usuarioEditar): ?>
        abrirModal();
    <?php endif; ?>
});
</script>

<?php include("../partials/footer.php"); ?>

</body>
</html>
