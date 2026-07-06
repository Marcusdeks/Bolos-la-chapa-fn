<?php

declare(strict_types=1);

require __DIR__ . '/../../includes/bootstrap.php';

Auth::requireAdmin();

$idUser = Auth::getUser()['idUser'];
$mensaje = "";
$noticiaEditar = null;

function guardarImagen(): string
{
    if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir imagen (código: ' . $_FILES['imagen']['error'] . ')');
    }

    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($_FILES['imagen']['type'], $allowed)) {
        throw new Exception('Formato de imagen inválido. Usa JPEG, PNG, WebP o GIF');
    }

    $maxSize = 5 * 1024 * 1024;
    if ($_FILES['imagen']['size'] > $maxSize) {
        throw new Exception('Imagen muy grande (máx 5MB)');
    }

    $dir = dirname(dirname(__DIR__)) . '/imagenes';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
    $nombre = uniqid('noticia_') . '.' . $ext;
    $ruta = $dir . '/' . $nombre;

    if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta)) {
        throw new Exception('Error al guardar imagen en: ' . $ruta);
    }

    return $nombre;
}

if (isset($_POST['crear'])) {
    try {
        $titulo = trim($_POST['titulo'] ?? '');
        $texto = trim($_POST['texto'] ?? '');
        $imagen = guardarImagen();

        if ($titulo && $texto) {
            Noticias::crear($idUser, $titulo, $texto, $imagen);
            $mensaje = "Noticia creada correctamente";
        } else {
            $mensaje = "Completa título y texto";
        }
    } catch (Exception $e) {
        $mensaje = $e->getMessage();
    }
}

if (isset($_POST['guardar'])) {
    try {
        $idEditar = (int) ($_POST['idNoticia'] ?? 0);
        $titulo = trim($_POST['titulo'] ?? '');
        $texto = trim($_POST['texto'] ?? '');
        $imagen = guardarImagen();

        if ($idEditar > 0 && $titulo && $texto) {
            Noticias::actualizar($idEditar, $titulo, $texto, $imagen);
            $mensaje = "Noticia actualizada correctamente";
        } else {
            $mensaje = "Completa título y texto";
        }
    } catch (Exception $e) {
        $mensaje = $e->getMessage();
    }
}

if (isset($_GET['borrar'])) {
    Noticias::eliminar((int) $_GET['borrar']);
    redirect('noticiasAdmin.php');
}

if (isset($_GET['editar'])) {
    $noticiaEditar = Noticias::getById((int) $_GET['editar']);
}

$porPagina = 8;
$paginaActual = max(1, (int) ($_GET['pag'] ?? 1));
$resultado = Noticias::getAllConTotal($paginaActual, $porPagina);
$noticias = $resultado['noticias'];
$totalNoticias = $resultado['total'];
$totalPaginas = max(1, (int) ceil($totalNoticias / $porPagina));
$paginaActual = min($paginaActual, $totalPaginas);

function urlPaginaN(int $p): string
{
    return 'noticiasAdmin.php?pag=' . $p;
}

$pagina = "noticiasAdmin";
?>

<!DOCTYPE html>
<html lang="es">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../../estilos/admin.css">
<title>Noticias Admin</title>
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
    <h2 class="h2Main">Noticias creadas</h2>
    <button type="button" class="btnCrearModal" id="btnAbrirModal">+ Crear noticia</button>
</div>

<?php if ($totalNoticias > 0): ?>
    <p class="infoPaginacion">
        <?php
            $desde = ($paginaActual - 1) * $porPagina + 1;
            $hasta = min($paginaActual * $porPagina, $totalNoticias);
            echo "Mostrando {$desde}–{$hasta} de {$totalNoticias} noticias";
        ?>
    </p>
<?php endif; ?>

<div class="tablaWrap">
<table class="tablaConciertos">

<tr>
<th>Título</th>
<th>Fecha</th>
<th>Autor</th>
<th>Acciones</th>
</tr>

<?php foreach($noticias as $fila){ ?>

<tr>
<td><?php echo htmlspecialchars($fila['titulo']); ?></td>
<td><?php echo htmlspecialchars($fila['fecha']); ?></td>
<td><?php echo htmlspecialchars(($fila['nombre'] ?? '') . ' ' . ($fila['apellidos'] ?? '')); ?></td>

<td class="tablaAdminAccion">
<a href="?editar=<?php echo (int) $fila['idNoticia']; ?>">Editar</a>
<a href="?borrar=<?php echo (int) $fila['idNoticia']; ?>">Borrar</a>
</td>
</tr>

<?php } ?>

</table>
</div>

<?php if ($totalPaginas > 1): ?>
    <nav class="paginacion" aria-label="Paginación de noticias">
        <?php if ($paginaActual > 1): ?>
            <a href="<?= esc(urlPaginaN($paginaActual - 1)) ?>" class="btnPag btnPagNav">← Anterior</a>
        <?php else: ?>
            <span class="btnPag btnPagNav desactivado">← Anterior</span>
        <?php endif; ?>

        <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
            <?php if ($p === $paginaActual): ?>
                <span class="btnPag activo"><?= $p ?></span>
            <?php else: ?>
                <a href="<?= esc(urlPaginaN($p)) ?>" class="btnPag"><?= $p ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($paginaActual < $totalPaginas): ?>
            <a href="<?= esc(urlPaginaN($paginaActual + 1)) ?>" class="btnPag btnPagNav">Siguiente →</a>
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
            <h2><?php echo $noticiaEditar ? 'Editar noticia' : 'Crear noticia'; ?></h2>
            <button type="button" class="btnCerrarModal" id="btnCerrarModal">✕</button>
        </div>

        <div class="modalBody">
            <?php if($mensaje !== ""){ ?>
            <p class="mensajeOk"><?php echo htmlspecialchars($mensaje); ?></p>
            <?php } ?>

            <form method="POST" enctype="multipart/form-data" class="formAdmin formAdminUnaColumna">

            <?php if($noticiaEditar){ ?>
            <input type="hidden" name="idNoticia" value="<?php echo (int) $noticiaEditar['idNoticia']; ?>">
            <?php } ?>

            <div class="formGrupo">
            <label>Título</label>
            <input type="text" name="titulo" value="<?php echo htmlspecialchars($noticiaEditar['titulo'] ?? ''); ?>" required>
            </div>

            <div class="formGrupo">
            <label>Imagen</label>
            <input type="file" name="imagen" accept="image/*" <?php echo !$noticiaEditar ? 'required' : ''; ?>>
            <?php if($noticiaEditar && $noticiaEditar['imagen']): ?>
            <p style="font-size: 0.85em; color: #888;">Actual: <?php echo htmlspecialchars($noticiaEditar['imagen']); ?></p>
            <?php endif; ?>
            </div>

            <div class="formGrupo">
            <label>Texto</label>
            <textarea name="texto" class="inputAdminTexto" required><?php echo htmlspecialchars($noticiaEditar['texto'] ?? ''); ?></textarea>
            </div>

            <div class="modalBotones">
                <button class="btnFormulario" type="submit" name="<?php echo $noticiaEditar ? 'guardar' : 'crear'; ?>">
                <?php echo $noticiaEditar ? 'Guardar' : 'Crear'; ?>
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

    <?php if ($noticiaEditar): ?>
        abrirModal();
    <?php endif; ?>
});
</script>

<?php include("../partials/footer.php"); ?>

</body>
</html>
