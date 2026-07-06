<?php

declare(strict_types=1);

require __DIR__ . '/../../includes/bootstrap.php';

Auth::requireAdmin();

$mensaje = getFlash('message');
$boloEditar = null;

// Usuario cuyas citas gestionamos. Llega por GET al elegirlo en el desplegable
// y se arrastra en cada acción para no perder la selección.
$usuarioSel = (int) ($_GET['usuario'] ?? 0);

// Lista de usuarios para el desplegable
$usuarios = Usuarios::getAll();

// GET: borrar una cita del usuario seleccionado
if (isset($_GET['borrar'])) {
    Conciertos::eliminar((int) $_GET['borrar']);
    setFlash('message', 'Concierto eliminado correctamente');
    redirect('bolosAdmin.php?usuario=' . $usuarioSel);
}

// GET: cargar una cita para editarla (abre el modal)
if (isset($_GET['editar'])) {
    $boloEditar = Conciertos::getById((int) $_GET['editar']);
    // Si no venía usuario en la URL, lo tomamos del propio concierto
    if ($boloEditar && !$usuarioSel) {
        $usuarioSel = (int) $boloEditar['idUser'];
    }
}

// POST: crear (para el usuario elegido) o actualizar una cita
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idUserObjetivo = (int) ($_POST['idUserObjetivo'] ?? 0);
    $grupo = trim($_POST['nombre_grupo'] ?? '');
    $estilo = trim($_POST['estilo_musica'] ?? '');
    $fecha = trim($_POST['fecha_concierto'] ?? '');
    $lugar = trim($_POST['lugar'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if (isset($_POST['crear'])) {
        if ($idUserObjetivo && $grupo && $estilo && $fecha && $lugar) {
            // La cita se crea A NOMBRE del usuario elegido, no del admin
            Conciertos::crear($idUserObjetivo, $grupo, $estilo, $fecha, $lugar, $descripcion)
                ? setFlash('message', 'Concierto creado para el usuario')
                : setFlash('message', 'Error al crear');
        } else {
            setFlash('message', 'Selecciona un usuario y completa todos los campos');
        }
        redirect('bolosAdmin.php?usuario=' . ($idUserObjetivo ?: $usuarioSel));
    }

    if (isset($_POST['guardar'])) {
        $id = (int) ($_POST['idConcierto'] ?? 0);
        if ($id > 0 && $grupo && $estilo && $fecha && $lugar) {
            Conciertos::actualizar($id, $grupo, $estilo, $fecha, $lugar, $descripcion)
                ? setFlash('message', 'Concierto actualizado correctamente')
                : setFlash('message', 'Error al actualizar');
        } else {
            setFlash('message', 'Completa todos los campos');
        }
        redirect('bolosAdmin.php?usuario=' . $usuarioSel);
    }
}

// Citas del usuario seleccionado (vacío si aún no se ha elegido a nadie)
$citasUsuario = $usuarioSel ? Conciertos::getMios($usuarioSel) : [];

// Nombre legible del usuario elegido (para los títulos)
$nombreUsuarioSel = '';
foreach ($usuarios as $u) {
    if ((int) $u['idUser'] === $usuarioSel) {
        $nombreUsuarioSel = $u['usuario'] . ' — ' . $u['nombre'] . ' ' . $u['apellidos'];
        break;
    }
}

$pagina = 'bolosAdmin';
?>

<!DOCTYPE html>
<html lang="es">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../../estilos/admin.css">
<title>Citas Admin</title>
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

<h2 class="h2Main">Gestión de conciertos por usuario</h2>

<?php if($mensaje !== ""){ ?>
<p class="mensajeOk"><?php echo htmlspecialchars($mensaje); ?></p>
<?php } ?>

<!-- Paso 1: elegir usuario -->
<form method="GET" class="selectorUsuario">
    <label for="usuario">Usuario:</label>
    <select name="usuario" id="usuario" onchange="this.form.submit()">
        <option value="0">— Elige un usuario —</option>
        <?php foreach ($usuarios as $u): ?>
            <option value="<?php echo (int) $u['idUser']; ?>" <?php echo ((int) $u['idUser'] === $usuarioSel) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($u['usuario'] . ' (' . $u['nombre'] . ' ' . $u['apellidos'] . ') · ' . $u['rol']); ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<?php if ($usuarioSel === 0): ?>

    <p class="introAside">Selecciona un usuario en el desplegable para ver, crear, editar o borrar sus conciertos.</p>

<?php else: ?>

    <div class="encabezadoNoticiasAdmin">
        <h2 class="h2Aside">Conciertos de <?php echo htmlspecialchars($nombreUsuarioSel); ?></h2>
        <button type="button" class="btnCrearModal" id="btnAbrirModal">+ Crear concierto</button>
    </div>

    <?php if (!empty($citasUsuario)): ?>
        <div class="tablaWrap">
        <table class="tablaConciertos">

        <tr>
        <th>Grupo</th>
        <th>Estilo</th>
        <th>Fecha</th>
        <th>Lugar</th>
        <th>Acciones</th>
        </tr>

        <?php foreach($citasUsuario as $fila){ ?>
        <tr>
        <td><?php echo htmlspecialchars($fila['nombre_grupo']); ?></td>
        <td><?php echo htmlspecialchars($fila['estilo_musica']); ?></td>
        <td><?php echo htmlspecialchars($fila['fecha_concierto']); ?></td>
        <td><?php echo htmlspecialchars($fila['lugar']); ?></td>
        <td class="tablaAdminAccion">
        <a href="?usuario=<?php echo $usuarioSel; ?>&editar=<?php echo (int) $fila['idConcierto']; ?>">Editar</a>
        <a href="?usuario=<?php echo $usuarioSel; ?>&borrar=<?php echo (int) $fila['idConcierto']; ?>" onclick="return confirm('¿Borrar este concierto?');">Borrar</a>
        </td>
        </tr>
        <?php } ?>

        </table>
        </div>
    <?php else: ?>
        <p class="agendaVacia">Este usuario aún no tiene conciertos.</p>
    <?php endif; ?>

<?php endif; ?>

</div>

</main>

<!-- Modal crear/editar -->
<div id="modalFormulario" class="modal">
    <div class="modalContenido">
        <div class="modalEncabezado">
            <h2><?php echo $boloEditar ? 'Editar concierto' : 'Crear concierto'; ?></h2>
            <button type="button" class="btnCerrarModal" id="btnCerrarModal">✕</button>
        </div>

        <div class="modalBody">
            <form method="POST" class="formAdmin formAdminUnaColumna">

            <input type="hidden" name="idUserObjetivo" value="<?php echo $usuarioSel; ?>">
            <?php if($boloEditar){ ?>
            <input type="hidden" name="idConcierto" value="<?php echo (int) $boloEditar['idConcierto']; ?>">
            <?php } ?>

            <div class="formGrupo">
            <label>Grupo</label>
            <input type="text" name="nombre_grupo" value="<?php echo htmlspecialchars($boloEditar['nombre_grupo'] ?? ''); ?>" required>
            </div>

            <div class="formGrupo">
            <label>Estilo</label>
            <input type="text" name="estilo_musica" value="<?php echo htmlspecialchars($boloEditar['estilo_musica'] ?? ''); ?>" required>
            </div>

            <div class="formGrupo">
            <label>Fecha</label>
            <input type="date" name="fecha_concierto" value="<?php echo htmlspecialchars($boloEditar['fecha_concierto'] ?? ''); ?>" required>
            </div>

            <div class="formGrupo">
            <label>Lugar</label>
            <input type="text" name="lugar" value="<?php echo htmlspecialchars($boloEditar['lugar'] ?? ''); ?>" required>
            </div>

            <div class="formGrupo">
            <label>Descripción</label>
            <textarea name="descripcion" class="inputAdminTexto" rows="3" placeholder="Detalles del concierto (opcional)"><?php echo htmlspecialchars($boloEditar['descripcion'] ?? ''); ?></textarea>
            </div>

            <div class="modalBotones">
                <button class="btnFormulario" type="submit" name="<?php echo $boloEditar ? 'guardar' : 'crear'; ?>">
                <?php echo $boloEditar ? 'Guardar' : 'Crear'; ?>
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

    <?php if ($boloEditar): ?>
        abrirModal();
    <?php endif; ?>
});
</script>

<?php include("../partials/footer.php"); ?>

</body>
</html>
