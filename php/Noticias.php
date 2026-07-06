<?php

declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';

// Obtener todas las noticias
$noticias = Noticias::getAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Noticias</title>
    <link rel="stylesheet" href="../estilos/noticias.css">
</head>
<body>

<header>
    <img class="logo" src="../imagenes/LogoBolosLaChapa.png" alt="Logo">
    <h2 class="tituloPrincipal">LOS MEJORES BOLOS PAL MEJOR PUBLICO</h2>
</header>

<?php
    $pagina = "noticias";
    $rutaBase = "../";
    include("partials/navBar.php");
?>

<main class="mainNoticias">
    <h1 class="h2Main">Noticias Musicales</h1>

    <?php foreach ($noticias as $fila): ?>
        <article class="noticia">
            <h2><?= esc($fila['titulo']) ?></h2>

            <p class="fecha">
                📅 Publicado el <?= date('d/m/Y', strtotime($fila['fecha'])) ?>
                <?php if ($fila['nombre']): ?>
                    <br>✍️ Por <?= esc($fila['nombre'] . ' ' . $fila['apellidos']) ?>
                <?php endif; ?>
            </p>

            <?php $imagen = trim((string) ($fila['imagen'] ?? '')); ?>
            <?php if ($imagen !== ''): ?>
                <?php $imagenSrc = (strpos($imagen, 'http://') === 0 || strpos($imagen, 'https://') === 0 || strpos($imagen, 'data:') === 0) ? $imagen : '../imagenes/' . ltrim($imagen, '/'); ?>
                <img class="imagenNoticia" src="<?= esc($imagenSrc) ?>" alt="<?= esc($fila['titulo']) ?>" loading="lazy" onerror="this.onerror=null;this.src='../imagenes/LogoBolosLaChapa.png';">
            <?php endif; ?>

            <p class="textoNoticia"><?= nl2br(esc($fila['texto'])) ?></p>
            
        </article>
    <?php endforeach; ?>

    <?php if (empty($noticias)): ?>
        <p class="sinNoticias">No hay noticias disponibles</p>
    <?php endif; ?>
</main>

<?php include("partials/footer.php"); ?>

</body>
</html>