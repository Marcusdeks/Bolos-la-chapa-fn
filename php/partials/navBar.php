<?php
/*
 * Vista parcial (partial): fragmento de HTML compartido que las páginas
 * incluyen con include(). No arranca sesión ni toca la base de datos:
 * eso ya lo hizo bootstrap.php antes de llegar aquí.
 *
 * $rutaBase la define cada página según su profundidad ("" en la raíz,
 * "../" en php/, "../../" en php/admin/) para que los enlaces funcionen
 * desde cualquier nivel.
 */
$rutaBase = isset($rutaBase) ? $rutaBase : "";
?>

<nav>

    <ul class="ulNav">

        <li>
            <a class="<?= ($pagina == 'inicio') ? 'seleccionado' : ''; ?>"
               href="<?= $rutaBase; ?>index.php">
               Inicio
            </a>
        </li>

        <li>
            <a class="<?= ($pagina == 'noticias') ? 'seleccionado' : ''; ?>"
               href="<?= $rutaBase; ?>php/Noticias.php">
               Noticias
            </a>
        </li>

        <?php if(isset($_SESSION['usuario'])){ ?>

        
        <li>
            <a class="<?= ($pagina == 'bolos') ? 'seleccionado' : ''; ?>"
               href="<?= $rutaBase; ?>php/bolos.php">
               Bolos
            </a>
        </li>

        <li>
            <a class="<?= ($pagina == 'perfil') ? 'seleccionado' : ''; ?>"
               href="<?= $rutaBase; ?>php/perfil.php">
               Perfil
            </a>
        </li>

        <?php if(isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'){ ?>
        <li>
            <a class="<?= ($pagina == 'noticiasAdmin') ? 'seleccionado' : ''; ?>"
               href="<?= $rutaBase; ?>php/admin/noticiasAdmin.php">
               Noticias Admin
            </a>
        </li>

        <li>
            <a class="<?= ($pagina == 'bolosAdmin') ? 'seleccionado' : ''; ?>"
               href="<?= $rutaBase; ?>php/admin/bolosAdmin.php">
               Bolos Admin
            </a>
        </li>

        <li>
            <a class="<?= ($pagina == 'usuariosAdmin') ? 'seleccionado' : ''; ?>"
               href="<?= $rutaBase; ?>php/admin/usuarioAdmin.php">
               Usuarios Admin
            </a>
        </li>
        <?php } ?>
        
        <button class="btnLog">
            <a href="<?= $rutaBase; ?>php/logout.php">Cerrar sesión</a>
        </button>

     <?php } else { ?>

        <button class="btnLog">
            <a href="<?= $rutaBase; ?>php/login.php">Iniciar sesión</a>
        </button>

    <?php } ?>
    </ul>

    

</nav>