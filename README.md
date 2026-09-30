# Bolos La Chapa

> **Los mejores bolos pal mejor público.**
> Aplicación web para descubrir, organizar y apuntarse a conciertos de metal, con noticias musicales, perfiles de usuario y un panel de administración completo.

Proyecto final de curso de desarrollo web, hecho **100 % en vanilla**: PHP sin frameworks, MySQL, HTML y CSS a mano. Sin Laravel, sin Bootstrap, sin Composer ni librerías externas. La idea era aprender cómo funcionan las cosas por dentro antes de usar herramientas que lo resuelven todo.

> En el enunciado original la entidad principal eran "citas". La adapté a **"bolos"** (conciertos) para que encajase con la temática del proyecto.

---

## Índice

- [Funcionalidades](#-funcionalidades)
- [Tecnologías](#-tecnologías)
- [Arquitectura y patrones](#-arquitectura-y-patrones)
- [Estructura del proyecto](#-estructura-del-proyecto)
- [Base de datos](#-base-de-datos)
- [Seguridad](#-seguridad)
- [Cómo probarlo](#-cómo-probarlo)
- [Usuarios de prueba](#-usuarios-de-prueba)
- [Guía de pruebas](#-guía-de-pruebas)
- [Qué he aprendido](#-qué-he-aprendido)

---

## Funcionalidades

### Visitantes (sin sesión)
- **Inicio**: presentación de la empresa y galería de conciertos organizados.
- **Noticias**: listado de noticias musicales con imagen, fecha y autor.
- **Registro** con validación en servidor (campos obligatorios, email válido, contraseña de al menos 6 caracteres y confirmación, usuario y email únicos).
- **Login** con contraseñas cifradas.

### Usuarios registrados (rol `user`)
- **Cartel de conciertos** (`Bolos`):
  - Ve los conciertos oficiales (creados por un admin) y los suyos propios.
  - **Búsqueda** por grupo, estilo y lugar (coincidencia parcial y combinable).
  - **Paginación** (6 por página) que conserva los filtros.
  - Etiquetas automáticas: `¡PRÓXIMAMENTE!` (menos de 30 días), `FINALIZADO` y `SOLO TÚ LO VES`.
- **Mi agenda**: apuntarse y desapuntarse de conciertos.
- **Crear conciertos propios** (privados): solo los ve quien los crea.
- **Editar y borrar** sus conciertos, solo si la fecha aún no ha pasado. Esta regla se comprueba **en el servidor**, no solo ocultando botones.
- **Mi perfil**: editar datos personales y cambiar la contraseña, que pide la actual.

### Administradores (rol `admin`)
- **Noticias Admin**: CRUD completo de noticias con **subida de imágenes** (JPEG, PNG, WebP o GIF, máx. 5 MB).
- **Bolos Admin**: CRUD de todos los conciertos, con búsqueda y paginación.
- **Usuarios Admin**: crear, editar (incluido rol y restablecer contraseña) y borrar usuarios. Al borrar un usuario se eliminan en cascada sus conciertos, noticias y asistencias.

---

## 🛠 Tecnologías

| Capa | Tecnología |
|------|------------|
| Backend | PHP 8.2 (`declare(strict_types=1)`, POO, extensión `mysqli`) |
| Base de datos | MySQL / MariaDB (InnoDB, `utf8mb4`) |
| Frontend | HTML5 + CSS3 puro (variables CSS, Flexbox, Grid, media queries) |
| Servidor local | XAMPP (Apache + MySQL) |
| Despliegue | Docker (PHP-Apache + MariaDB) en Render |

**Sin** frameworks, **sin** gestores de dependencias y **sin** JavaScript de librerías: todo está escrito a mano.

---

## Arquitectura y patrones

Aunque es vanilla, el código está organizado siguiendo patrones reales para que no sea una "sopa de PHP y HTML":

| Patrón | Dónde | Para qué |
|--------|-------|----------|
| **Bootstrap / punto de entrada único** | `includes/bootstrap.php` | Todas las páginas cargan un único archivo que prepara configuración, sesión, autoloader y helpers. |
| **Autoloading** (base de PSR-4) | `spl_autoload_register` en `bootstrap.php` | Las clases se cargan solas la primera vez que se usan. Añadir una clase nueva no obliga a tocar nada más. |
| **Singleton** | `includes/core/Database.php` | Una sola conexión `mysqli` compartida por petición. |
| **Facade** | `includes/core/Auth.php` | Nadie toca `$_SESSION` directamente. Incluye los "guardias" `requireLogin()` y `requireAdmin()`. |
| **Table Data Gateway** | `includes/models/*.php` | Todo el SQL vive en los modelos. Las páginas solo llaman a métodos con nombres de negocio (`apuntarse`, `buscarConTotal`…). |
| **Configuración por entorno** | `includes/core/Config.php` | Credenciales leídas de variables de entorno, con valores por defecto para XAMPP. El mismo código funciona en local y en producción. |
| **Vistas parciales** | `php/partials/` | Barra de navegación y pie compartidos, con menú dinámico según el rol. |
| **PRG + mensajes flash** | `bolos.php`, `helpers.php` | Tras un POST se redirige, y el mensaje sobrevive a la redirección vía sesión. Así, al recargar no se reenvía el formulario. |

---

## Estructura del proyecto

```
bolosLaChapa/
├── index.php                 # Página de inicio
├── database.sql              # Esquema completo + datos de ejemplo
├── Dockerfile                # Imagen para desplegar (Render)
├── docker-entrypoint.sh      # Arranca MariaDB, carga la BD y lanza Apache
├── includes/
│   ├── bootstrap.php         # Punto de entrada: autoloader, sesión, helpers
│   ├── helpers.php           # esc(), redirect(), setFlash()/getFlash()
│   ├── core/                 # Infraestructura genérica y reutilizable
│   │   ├── Auth.php
│   │   ├── Config.php
│   │   └── Database.php
│   └── models/               # Acceso a datos del dominio
│       ├── Conciertos.php
│       ├── Noticias.php
│       └── Usuarios.php
├── php/
│   ├── bolos.php             # Cartel, agenda y conciertos propios
│   ├── Noticias.php          # Noticias públicas
│   ├── login.php · logout.php · registro.php · perfil.php
│   ├── admin/                # Panel de administración (solo rol admin)
│   │   ├── bolosAdmin.php
│   │   ├── noticiasAdmin.php
│   │   └── usuarioAdmin.php
│   └── partials/             # navBar.php, footer.php
├── estilos/                  # Una hoja por sección + estilos.css común
└── imagenes/                 # Logo, fondos e imágenes de noticias
```

---

## Base de datos

Base de datos `bolos_la_chapa` con 5 tablas relacionadas mediante claves foráneas con `ON DELETE CASCADE`:

```
users_data (1) ──── (1) users_login        datos personales ↔ credenciales + rol
     │
     ├──── (N) noticias                     autor de cada noticia
     ├──── (N) conciertos                   creador de cada concierto
     └──── (N) asistencias (N) ──── conciertos
                 └─ tabla intermedia N:M: la "agenda" de cada usuario
```

- `users_data`: nombre, apellidos, email (único), teléfono, fecha de nacimiento, dirección y sexo.
- `users_login`: usuario (único), contraseña hasheada y rol (`admin` / `user`).
- `noticias`: título (único), imagen, texto y fecha.
- `conciertos`: grupo, estilo, fecha, lugar y descripción.
- `asistencias`: clave primaria compuesta `(idUser, idConcierto)`, así nadie puede apuntarse dos veces.

---

## Seguridad

- **Contraseñas** cifradas con `password_hash()` (bcrypt) y comprobadas con `password_verify()`. Nunca se guardan en claro.
- **Consultas preparadas** (`prepare` + `bind_param`) en todos los datos que vienen del usuario, para evitar **inyección SQL**.
- **Escapado de salida** con `htmlspecialchars` (helper `esc()`) contra **XSS**.
- **Control de acceso en servidor**: las páginas protegidas se bloquean con `Auth::requireLogin()` o `Auth::requireAdmin()`, y los permisos de edición se comprueban otra vez al procesar cada acción.
- **Subida de imágenes** validada por tipo y tamaño, con nombre de archivo generado (`uniqid`).
- `display_errors` desactivado: los errores van al log, no se muestran al visitante.

---

## Cómo probarlo

### Opción A: demo online

https://bolos-la-chapa-fn.onrender.com

> Está en el plan gratuito de Render. Si lleva un rato sin visitas, la primera carga tarda unos 30–60 segundos en "despertar". La base de datos **se reinicia** con los datos de ejemplo cada vez que el servicio arranca, así que puedes probar lo que quieras sin miedo.

### Opción B: en local con XAMPP

1. Instala [XAMPP](https://www.apachefriends.org/) (PHP 8.x).
2. Clona el repositorio dentro de `htdocs`:
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/Marcusdeks/Bolos-la-chapa-fn.git bolosLaChapa
   ```
3. Abre el panel de XAMPP y arranca **Apache** y **MySQL**.
4. Importa la base de datos de una de estas dos formas:
   - **phpMyAdmin**: entra en <http://localhost/phpmyadmin>, pestaña **Importar** y selecciona `database.sql`.
   - **Consola**:
     ```bash
     C:\xampp\mysql\bin\mysql.exe -u root < database.sql
     ```
   El script crea la base de datos `bolos_la_chapa` automáticamente.
5. Abre <http://localhost/bolosLaChapa/>.

> La conexión usa por defecto `root` sin contraseña en `127.0.0.1:3306`, que es la configuración de XAMPP. Si tu MySQL es distinto, define las variables de entorno `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASSWORD` y `DB_NAME`.

### Opción C: con Docker

```bash
docker build -t bolos-la-chapa .
docker run -p 8080:10000 bolos-la-chapa
```

Y abre <http://localhost:8080>.

---

## Usuarios de prueba

| Rol | Usuario | Contraseña |
|-----|---------|------------|
| Administrador | `Sabbath` | `MasterD1` |
| Usuario normal | `Profe` | `MasterD` |

También puedes registrar un usuario nuevo desde **Iniciar sesión → Regístrate**.

---

## Guía de pruebas

Un recorrido rápido para ver todo en unos 5 minutos:

**1. Como visitante**
- [ ] Navega por **Inicio** y **Noticias**.
- [ ] Intenta entrar en `/php/bolos.php` sin sesión: te redirige al login.

**2. Registro y usuario normal**
- [ ] Regístrate con una contraseña corta o que no coincida: verás los mensajes de error.
- [ ] Inicia sesión como `Profe` / `MasterD`.
- [ ] En **Bolos**, busca por estilo (`Black`) o lugar (`Madrid`) y usa la paginación.
- [ ] **Apúntate** a un concierto y comprueba que aparece en **Mi Agenda**. Luego quítalo.
- [ ] **Crea** un concierto propio: aparece con la etiqueta `SOLO TÚ LO VES`. Edítalo y bórralo.
- [ ] Fíjate en que los conciertos pasados salen como `FINALIZADO` y no se pueden editar.
- [ ] En **Perfil**, cambia tus datos y tu contraseña.
- [ ] Intenta entrar en `/php/admin/usuarioAdmin.php`: te echa, porque no eres admin.

**3. Como administrador**
- [ ] Cierra sesión y entra como `Sabbath` / `MasterD1`: aparecen tres menús de administración.
- [ ] **Noticias Admin**: crea una noticia con imagen y comprueba que sale en **Noticias**.
- [ ] **Bolos Admin**: crea un concierto oficial. Luego entra como `Profe` y verás que ahora sí le aparece.
- [ ] **Usuarios Admin**: edita un usuario, cámbiale el rol o restablece su contraseña.

---

## Qué he aprendido

- Estructurar una aplicación PHP **sin framework**: separar infraestructura (`core`), acceso a datos (`models`) y vistas.
- Aplicar **patrones de diseño** (Singleton, Facade, Table Data Gateway, Autoloading) y entender *por qué* existen.
- Diseñar una **base de datos relacional** con relaciones 1:1, 1:N y N:M, e integridad referencial en cascada.
- **Seguridad web** básica: hashing de contraseñas, consultas preparadas, escapado XSS y autorización en servidor.
- Gestión de **sesiones y roles**, búsqueda combinada con **paginación** en SQL (`LIMIT`/`OFFSET`) y subida de archivos.
- Maquetación **responsive** con CSS puro.
- **Contenerizar** la aplicación con Docker y desplegarla en la nube.

---

## Autor

**Marc Carretero**: [GitHub @Marcusdeks](https://github.com/Marcusdeks)

Proyecto final de curso de desarrollo web. 🤘
