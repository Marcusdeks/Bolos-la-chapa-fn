<?php

declare(strict_types=1);

/*
 * bootstrap.php — punto de entrada único de la aplicación.
 *
 * Toda página hace `require .../includes/bootstrap.php` como primera línea
 * y con eso tiene disponible TODO: configuración, base de datos, sesión,
 * clases de dominio y funciones auxiliares. Es el patrón "Bootstrap /
 * Front Controller simplificado": un solo sitio donde arranca la app,
 * en vez de que cada página repita su propia inicialización.
 *
 * Estructura de includes/:
 *   core/    → infraestructura genérica (Config, Database, Auth).
 *              No sabe nada de conciertos ni noticias; podría reutilizarse
 *              en cualquier otro proyecto tal cual.
 *   models/  → acceso a datos del dominio (Conciertos, Noticias, Usuarios).
 *              Una clase por entidad; todo el SQL vive aquí y las páginas
 *              nunca tocan la base de datos directamente.
 *   helpers.php → funciones sueltas de uso general (esc, redirect, flash).
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// URL base del proyecto (p. ej. "/bolosLaChapa" en XAMPP, "" si es la raíz del servidor).
// Se calcula comparando la carpeta del proyecto con el document root.
$docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$projectRoot = str_replace('\\', '/', dirname(__DIR__));
define('BASE_URL', ($docRoot !== '' && stripos($projectRoot, $docRoot) === 0)
    ? substr($projectRoot, strlen($docRoot))
    : '');

/*
 * Autoloader (patrón Autoloading, la base de PSR-4):
 * en vez de mantener aquí una lista de `require` que hay que actualizar
 * con cada clase nueva, registramos una función que PHP llama
 * automáticamente la PRIMERA vez que el código usa una clase aún no
 * cargada. Busca el archivo <NombreClase>.php en core/ y models/.
 *
 * Ventaja extra: solo se carga lo que la página usa de verdad.
 * Para añadir una clase nueva (p. ej. Comentarios) basta con crear
 * models/Comentarios.php — sin tocar este archivo.
 */
spl_autoload_register(function (string $clase): void {
    foreach (['core', 'models'] as $carpeta) {
        $ruta = __DIR__ . '/' . $carpeta . '/' . $clase . '.php';
        if (is_file($ruta)) {
            require $ruta;
            return;
        }
    }
});

// Las funciones sueltas no pueden autocargarse (el autoloader solo
// funciona con clases), así que estas sí se cargan siempre.
require __DIR__ . '/helpers.php';

Auth::start();
