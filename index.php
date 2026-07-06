<?php
require __DIR__ . '/includes/bootstrap.php';
$pagina = "inicio";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="estilos/index.css">
    <title>Bolos La Chapa - Inicio</title>
</head>
<body>
    <header><img class="logo" src="imagenes/LogoBolosLaChapa.png" alt="Logo">
        <h2 class="tituloPrincipal">LOS MEJORES BOLOS PAL MEJOR PUBLICO</h2>
    </header>
<!--NAV-------------------------->
    <?php
        $pagina = "inicio";
        include("php/partials/navBar.php");
    ?>
<!--NAV-------------------------->
    <div class="contenido">
        
        <aside>
            <h2>Sobre nosotros</h2>
            <p>Somos una empresa dedicada a la venta y organización de bolos.</p> <br>
            <p>Los creadores somos Juan Pérez y María López. <br>
                Iniciamos esta idea en 2020 con el objetivo de ofrecer a los amantes de la música una facilitación a la hora de organizar, encontrar y disfrutar de conciertos. <br>
            </p>
            <img src="imagenes/manoCornuta.jpg" alt="mano cornuta" class="manoCornuta" >
        </aside>
        <main>

            <h2  class="h2Main">Conciertos organizados y participaciones</h2>
            <div class="gridMain">
                <article class="cardConcierto">
                    <a href="https://judaspriest.com/"><img class="imagenConcierto" src="imagenes/judas.webp" alt="Concierto de rock en sala"></a>
                    <p class="descripcionConcierto">Judas Priest en una gloriosa noche, la caña original del heavy metal ingles transportada de forma intacta a nuestra era.</p>
                </article>
                <article class="cardConcierto">
                    <a href="https://www.metallica.com/"><img class="imagenConcierto" src="imagenes/Metallica.PNG" alt="Público levantando la mano en un concierto"></a>
                    <p class="descripcionConcierto">Metallica en un concierto inolvidable, dandolo todo como siempre.</p>
                </article>
                <article class="cardConcierto">
                    <a href="https://huestenegra.bandcamp.com/album/ryma-e-morte">
                    <img class="imagenConcierto" src="imagenes/HuesteNegra.jpg" alt="Escenario iluminado durante un concierto"></a>
                    <p class="descripcionConcierto">Hueste Negra, el grupo emergente que esta partiendo la pana, les organizamos la fecha, enorme debut y banda que promete muchisimo, concierto inolvidable.</p>
                </article>
                <article class="cardConcierto">
                    <a href="https://www.acceptworldwide.com/"><img class="imagenConcierto" src="imagenes/Accept.jpg" alt="Concierto de rock en sala"></a>
                    <p class="descripcionConcierto">Noche de pura caña con aforo completo y una puesta en escena increible.</p>
                </article>
                <article class="cardConcierto">
                    <a href="https://www.ironmaiden.com/"><img class="imagenConcierto" src="imagenes/Iron.PNG" alt="Público levantando la mano en un concierto"></a>
                    <p class="descripcionConcierto">Devolviendole el sentido al heavy metal con una actuación memorable.</p>
                </article>
                <article class="cardConcierto">
                    <a href="https://www.helloween.org/"><img class="imagenConcierto" src="imagenes/Helloween.jpg" alt="Escenario iluminado durante un concierto"></a>
                    <p class="descripcionConcierto">Evento temático con artistas emergentes y una producción visual enfocada en la experiencia del público, atrayendo tambien a grandes multitudes con grupazos como Helloween.</p>
                </article>
            </div>
            
                
            
        
        </main>
    </div>
    <?php include("php/partials/footer.php"); ?>
        
</body>
</html>