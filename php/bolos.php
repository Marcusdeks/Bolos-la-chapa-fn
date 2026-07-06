<?php

declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';

Auth::requireLogin();

$idUser = Auth::getUser()['idUser'];
$mensaje = getFlash('message');
$hoy = date('Y-m-d');

/**
 * Un usuario solo puede editar o borrar SUS propios conciertos, y únicamente
 * si la fecha no es anterior a hoy (no se tocan citas ya realizadas).
 * Esta comprobación se hace SIEMPRE en el servidor: no basta con ocultar los
 * botones, hay que impedir la acción aunque manden el formulario a mano.
 */
function usuarioPuedeGestionar(?array $concierto, int $idUser, string $hoy): bool
{
    return $concierto !== null
        && (int) $concierto['idUser'] === $idUser
        && $concierto['fecha_concierto'] >= $hoy;
}

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $grupo = trim($_POST['nombre_grupo'] ?? '');
        $estilo = trim($_POST['estilo_musica'] ?? '');
        $fecha = trim($_POST['fecha_concierto'] ?? '');
        $lugar = trim($_POST['lugar'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        if ($grupo && $estilo && $fecha && $lugar) {
            Conciertos::crear($idUser, $grupo, $estilo, $fecha, $lugar, $descripcion)
                ? setFlash('message', '¡Concierto añadido al cartel! \m/')
                : setFlash('message', 'Error al crear concierto');
        } else {
            setFlash('message', 'Completa todos los campos');
        }
        redirect('bolos.php');
    }

    if ($accion === 'editar_guardar') {
        $id = (int) ($_POST['idConcierto'] ?? 0);
        $original = Conciertos::getById($id);

        if (!usuarioPuedeGestionar($original, $idUser, $hoy)) {
            setFlash('message', 'No puedes editar ese concierto');
        } else {
            $grupo = trim($_POST['nombre_grupo'] ?? '');
            $estilo = trim($_POST['estilo_musica'] ?? '');
            $fecha = trim($_POST['fecha_concierto'] ?? '');
            $lugar = trim($_POST['lugar'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');

            if (!$grupo || !$estilo || !$fecha || !$lugar) {
                setFlash('message', 'Completa todos los campos');
            } elseif ($fecha < $hoy) {
                setFlash('message', 'La fecha no puede ser anterior a hoy');
            } else {
                Conciertos::actualizar($id, $grupo, $estilo, $fecha, $lugar, $descripcion)
                    ? setFlash('message', 'Concierto actualizado')
                    : setFlash('message', 'Error al actualizar');
            }
        }
        redirect('bolos.php');
    }

    if ($accion === 'borrar') {
        $id = (int) ($_POST['idConcierto'] ?? 0);
        $original = Conciertos::getById($id);

        if (!usuarioPuedeGestionar($original, $idUser, $hoy)) {
            setFlash('message', 'No puedes borrar ese concierto');
        } else {
            Conciertos::eliminar($id);
            setFlash('message', 'Concierto borrado');
        }
        redirect('bolos.php');
    }

    if ($accion === 'apuntarse') {
        Conciertos::apuntarse($idUser, (int) ($_POST['idConcierto'] ?? 0));
        setFlash('message', '¡Apuntado al concierto! Nos vemos en primera fila 🤘');
        redirect('bolos.php');
    }

    if ($accion === 'quitarse') {
        Conciertos::quitarse($idUser, (int) ($_POST['idConcierto'] ?? 0));
        setFlash('message', 'Te has quitado del concierto');
        redirect('bolos.php');
    }
}

// Filtros de búsqueda (GET)
$qGrupo = trim($_GET['grupo'] ?? '');
$qEstilo = trim($_GET['estilo'] ?? '');
$qLugar = trim($_GET['lugar'] ?? '');
$hayBusqueda = ($qGrupo !== '' || $qEstilo !== '' || $qLugar !== '');

$porPagina = 6;
$paginaActual = max(1, (int) ($_GET['pag'] ?? 1));

$resultado = Conciertos::buscarConTotal($qGrupo, $qEstilo, $qLugar, $paginaActual, $porPagina, $idUser);
$todosLosConciertos = $resultado['conciertos'];
$totalConciertos = $resultado['total'];
$totalPaginas = max(1, (int) ceil($totalConciertos / $porPagina));
$paginaActual = min($paginaActual, $totalPaginas);

$agenda = Conciertos::getAgenda($idUser);
$idsAgenda = Conciertos::getIdsAgenda($idUser);

// ¿El usuario pidió editar un concierto suyo? Solo se acepta si es propio y futuro.
$boloEditar = null;
if (isset($_GET['editar'])) {
    $posible = Conciertos::getById((int) $_GET['editar']);
    if (usuarioPuedeGestionar($posible, $idUser, $hoy)) {
        $boloEditar = $posible;
    }
}

function urlPagina(int $p, string $grupo, string $estilo, string $lugar): string
{
    $params = array_filter(['grupo' => $grupo, 'estilo' => $estilo, 'lugar' => $lugar]);
    $params['pag'] = $p;
    return 'bolos.php?' . http_build_query($params);
}

$pagina = 'bolos';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bolos - Los Mejores Conciertos</title>
    <link rel="stylesheet" href="../estilos/bolos.css">
</head>
<body>

<header>
    <img class="logo" src="../imagenes/LogoBolosLaChapa.png" alt="Logo">
    <h2 class="tituloPrincipal">LOS MEJORES BOLOS PAL MEJOR PUBLICO</h2>
</header>

<?php
    $rutaBase = '../';
    include("partials/navBar.php");
?>

<main class="mainBolos">

    <?php if ($mensaje): ?>
        <div class="alertaMensaje exito">
            <span>✓</span> <?= esc($mensaje) ?>
        </div>
    <?php endif; ?>

    <section class="seccionHero">
        <h1>🎸 CARTEL DE CONCIERTOS</h1>
        <p class="subtitulo">Apúntate a los mejores bolos y arma tu agenda</p>
    </section>

    <div class="contenedorBolos">
        <!-- Columna izquierda: Formulario crear -->
        <aside class="formularioAside">
            <div class="cardFormulario">
                <h2><?= $boloEditar ? 'Editar Concierto' : 'Añadir Concierto' ?></h2>
                <p class="agendaSub">
                    <?= $boloEditar
                        ? 'Modifica los datos de tu concierto.'
                        : 'Los conciertos que crees aquí solo los verás tú.' ?>
                </p>
                <form method="POST" class="formuBolos">
                    <input type="hidden" name="accion" value="<?= $boloEditar ? 'editar_guardar' : 'crear' ?>">
                    <?php if ($boloEditar): ?>
                        <input type="hidden" name="idConcierto" value="<?= (int) $boloEditar['idConcierto'] ?>">
                    <?php endif; ?>

                    <div class="grupoInput">
                        <label>Banda / Grupo</label>
                        <input type="text" name="nombre_grupo" value="<?= esc($boloEditar['nombre_grupo'] ?? '') ?>" placeholder="Ej: Metallica, Iron Maiden..." required>
                    </div>
                    <div class="grupoInput">
                        <label>Género Musical</label>
                        <input type="text" name="estilo_musica" value="<?= esc($boloEditar['estilo_musica'] ?? '') ?>" placeholder="Ej: Heavy Metal, Thrash..." required>
                    </div>
                    <div class="grupoInput">
                        <label>Fecha del Concierto</label>
                        <input type="date" name="fecha_concierto" value="<?= esc($boloEditar['fecha_concierto'] ?? '') ?>" min="<?= $hoy ?>" required>
                    </div>
                    <div class="grupoInput">
                        <label>Lugar</label>
                        <input type="text" name="lugar" value="<?= esc($boloEditar['lugar'] ?? '') ?>" placeholder="Ej: Madrid, Barcelona..." required>
                    </div>
                    <div class="grupoInput">
                        <label>Descripción</label>
                        <textarea name="descripcion" rows="3" placeholder="Detalles del concierto (opcional): teloneros, hora, precio..."><?= esc($boloEditar['descripcion'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btnCrearBolo">
                        <?= $boloEditar ? '💾 Guardar cambios' : '🎵 Crear Concierto' ?>
                    </button>
                    <?php if ($boloEditar): ?>
                        <a href="bolos.php" class="btnLimpiar btnCancelarEdicion">Cancelar edición</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Mi Agenda -->
            <div class="cardAgenda">
                <h2>📋 Mi Agenda</h2>
                <p class="agendaSub">Conciertos a los que vas</p>

                <?php if (!empty($agenda)): ?>
                    <ul class="listaAgenda">
                        <?php foreach ($agenda as $evento): ?>
                            <li class="itemAgenda">
                                <div class="itemAgendaInfo">
                                    <span class="itemAgendaGrupo"><?= esc($evento['nombre_grupo']) ?></span>
                                    <span class="itemAgendaMeta">
                                        <?= date('d/m/Y', strtotime($evento['fecha_concierto'])) ?> · <?= esc($evento['lugar']) ?>
                                    </span>
                                </div>
                                <form method="POST" class="formQuitar">
                                    <input type="hidden" name="accion" value="quitarse">
                                    <input type="hidden" name="idConcierto" value="<?= (int) $evento['idConcierto'] ?>">
                                    <button type="submit" class="btnQuitar" title="Quitar de mi agenda">✕</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="agendaVacia">Aún no te has apuntado a ningún concierto.</p>
                <?php endif; ?>
            </div>
        </aside>

        <!-- Columna derecha: Listado de conciertos -->
        <section class="listadoConciertos">
            <h2>Conciertos en Cartel</h2>

            <!-- Búsqueda -->
            <form method="GET" class="barraBusqueda">
                <input type="text" name="grupo" value="<?= esc($qGrupo) ?>" placeholder="🔍 Grupo">
                <input type="text" name="estilo" value="<?= esc($qEstilo) ?>" placeholder="Estilo">
                <input type="text" name="lugar" value="<?= esc($qLugar) ?>" placeholder="Lugar">
                <button type="submit" class="btnBuscar">Buscar</button>
                <?php if ($hayBusqueda): ?>
                    <a href="bolos.php" class="btnLimpiar">Limpiar</a>
                <?php endif; ?>
            </form>

            <?php if ($totalConciertos > 0): ?>
                <p class="infoPaginacion">
                    <?php
                        $desde = ($paginaActual - 1) * $porPagina + 1;
                        $hasta = min($paginaActual * $porPagina, $totalConciertos);
                        echo "Mostrando {$desde}–{$hasta} de {$totalConciertos} conciertos";
                    ?>
                </p>
            <?php endif; ?>

            <?php if (!empty($todosLosConciertos)): ?>
                <div class="gridConciertos">
                    <?php foreach ($todosLosConciertos as $concierto): ?>
                        <?php
                            $fecha = new DateTime($concierto['fecha_concierto']);
                            $fechaFormato = $fecha->format('d M Y');
                            $dias = (int) $fecha->diff(new DateTime())->format('%r%a');
                            $apuntado = in_array((int) $concierto['idConcierto'], $idsAgenda, true);
                            $esPropio = (int) $concierto['idUser'] === $idUser;
                            $esFuturo = $concierto['fecha_concierto'] >= $hoy;
                        ?>
                        <article class="tarjetaConcierto<?= $apuntado ? ' apuntado' : '' ?>">
                            <div class="headerTarjeta">
                                <span class="generoTag"><?= esc($concierto['estilo_musica']) ?></span>
                                <?php if (!Auth::isAdmin() && (int) $concierto['idUser'] === $idUser): ?>
                                    <span class="badge privado">SOLO TÚ LO VES</span>
                                <?php endif; ?>
                                <?php if ($dias >= 0 && $dias < 30): ?>
                                    <span class="badge pronto">¡PRÓXIMAMENTE!</span>
                                <?php elseif ($dias < 0): ?>
                                    <span class="badge pasado">FINALIZADO</span>
                                <?php endif; ?>
                            </div>

                            <div class="cuerpoTarjeta">
                                <h3><?= esc($concierto['nombre_grupo']) ?></h3>

                                <div class="detalles">
                                    <div class="detalle">
                                        <span class="icono">📅</span>
                                        <div>
                                            <p class="etiqueta">Fecha</p>
                                            <p class="valor"><?= $fechaFormato ?></p>
                                        </div>
                                    </div>
                                    <div class="detalle">
                                        <span class="icono">📍</span>
                                        <div>
                                            <p class="etiqueta">Lugar</p>
                                            <p class="valor"><?= esc($concierto['lugar']) ?></p>
                                        </div>
                                    </div>
                                    <div class="detalle">
                                        <span class="icono">🎤</span>
                                        <div>
                                            <p class="etiqueta">Organizado por</p>
                                            <p class="valor"><?= esc($concierto['usuario'] ?? 'Anónimo') ?></p>
                                        </div>
                                    </div>
                                    <?php if (!empty($concierto['descripcion'])): ?>
                                    <div class="detalle">
                                        <span class="icono">📝</span>
                                        <div>
                                            <p class="etiqueta">Descripción</p>
                                            <p class="valor"><?= esc($concierto['descripcion']) ?></p>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="pieTarjeta">
                                <?php if ($esPropio): ?>
                                    <?php if ($esFuturo): ?>
                                        <div class="accionesPropias">
                                            <a href="?editar=<?= (int) $concierto['idConcierto'] ?>" class="btnApunte editar">✏️ Editar</a>
                                            <form method="POST" class="formApunte" onsubmit="return confirm('¿Seguro que quieres borrar este concierto?');">
                                                <input type="hidden" name="accion" value="borrar">
                                                <input type="hidden" name="idConcierto" value="<?= (int) $concierto['idConcierto'] ?>">
                                                <button type="submit" class="btnApunte borrar">🗑️ Borrar</button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <p class="conciertoRealizado">Concierto ya realizado</p>
                                    <?php endif; ?>
                                <?php elseif ($apuntado): ?>
                                    <form method="POST" class="formApunte">
                                        <input type="hidden" name="accion" value="quitarse">
                                        <input type="hidden" name="idConcierto" value="<?= (int) $concierto['idConcierto'] ?>">
                                        <button type="submit" class="btnApunte quitarse">✓ Apuntado — Quitarme</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" class="formApunte">
                                        <input type="hidden" name="accion" value="apuntarse">
                                        <input type="hidden" name="idConcierto" value="<?= (int) $concierto['idConcierto'] ?>">
                                        <button type="submit" class="btnApunte apuntarse">🤘 Apuntarme</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPaginas > 1): ?>
                    <nav class="paginacion" aria-label="Paginación de conciertos">
                        <?php if ($paginaActual > 1): ?>
                            <a href="<?= esc(urlPagina($paginaActual - 1, $qGrupo, $qEstilo, $qLugar)) ?>" class="btnPag btnPagNav">← Anterior</a>
                        <?php else: ?>
                            <span class="btnPag btnPagNav desactivado">← Anterior</span>
                        <?php endif; ?>

                        <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
                            <?php if ($p === $paginaActual): ?>
                                <span class="btnPag activo"><?= $p ?></span>
                            <?php else: ?>
                                <a href="<?= esc(urlPagina($p, $qGrupo, $qEstilo, $qLugar)) ?>" class="btnPag"><?= $p ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($paginaActual < $totalPaginas): ?>
                            <a href="<?= esc(urlPagina($paginaActual + 1, $qGrupo, $qEstilo, $qLugar)) ?>" class="btnPag btnPagNav">Siguiente →</a>
                        <?php else: ?>
                            <span class="btnPag btnPagNav desactivado">Siguiente →</span>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <div class="sinConciertos">
                    <div class="iconoVacio">🎸</div>
                    <h3><?= $hayBusqueda ? 'Sin resultados' : 'Sin conciertos aún' ?></h3>
                    <p><?= $hayBusqueda ? 'Prueba con otros filtros de búsqueda' : 'Sé el primero en añadir un concierto al cartel' ?></p>
                </div>
            <?php endif; ?>
        </section>
    </div>

</main>

<?php include("partials/footer.php"); ?>

</body>
</html>
