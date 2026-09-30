-- ================================================
-- Bolos La Chapa — Base de datos completa + datos de ejemplo
-- Importar en phpMyAdmin (pestaña "Importar") o por consola:
--   mysql -u root -p < database.sql
-- ================================================

CREATE DATABASE IF NOT EXISTS bolos_la_chapa
    DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE bolos_la_chapa;

SET FOREIGN_KEY_CHECKS = 0;

-- Tabla de datos de usuarios
DROP TABLE IF EXISTS users_data;
CREATE TABLE users_data (
    idUser INT(11) NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    direccion VARCHAR(255) DEFAULT NULL,
    sexo ENUM('Hombre','Mujer','Otro') DEFAULT NULL,
    PRIMARY KEY (idUser),
    UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de usuarios (login)
DROP TABLE IF EXISTS users_login;
CREATE TABLE users_login (
    idLogin INT(11) NOT NULL AUTO_INCREMENT,
    idUser INT(11) NOT NULL,
    usuario VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin','user') NOT NULL,
    PRIMARY KEY (idLogin),
    UNIQUE KEY idUser (idUser),
    UNIQUE KEY usuario (usuario),
    CONSTRAINT fk_login_user FOREIGN KEY (idUser) REFERENCES users_data (idUser) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de noticias
DROP TABLE IF EXISTS noticias;
CREATE TABLE noticias (
    idNoticia INT(11) NOT NULL AUTO_INCREMENT,
    titulo VARCHAR(200) NOT NULL,
    imagen VARCHAR(255) NOT NULL,
    texto TEXT NOT NULL,
    fecha DATE NOT NULL,
    idUser INT(11) NOT NULL,
    PRIMARY KEY (idNoticia),
    UNIQUE KEY titulo (titulo),
    KEY fk_noticia_user (idUser),
    CONSTRAINT fk_noticia_user FOREIGN KEY (idUser) REFERENCES users_data (idUser) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de conciertos
DROP TABLE IF EXISTS conciertos;
CREATE TABLE conciertos (
    idConcierto INT(11) NOT NULL AUTO_INCREMENT,
    idUser INT(11) NOT NULL,
    fecha_concierto DATE NOT NULL,
    estilo_musica VARCHAR(100) NOT NULL,
    nombre_grupo VARCHAR(150) NOT NULL,
    lugar VARCHAR(150) NOT NULL,
    descripcion VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (idConcierto),
    KEY fk_concierto_user (idUser),
    CONSTRAINT fk_concierto_user FOREIGN KEY (idUser) REFERENCES users_data (idUser) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de asistencias (agenda: usuarios apuntados a conciertos)
DROP TABLE IF EXISTS asistencias;
CREATE TABLE asistencias (
    idUser INT(11) NOT NULL,
    idConcierto INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (idUser, idConcierto),
    CONSTRAINT fk_asistencia_user FOREIGN KEY (idUser) REFERENCES users_data (idUser) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_asistencia_concierto FOREIGN KEY (idConcierto) REFERENCES conciertos (idConcierto) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ================================================
-- Datos de ejemplo
-- ================================================

INSERT INTO users_data VALUES
    (1,'Carlos','Martinez','carlos@gmail.com','666111222','2000-05-10','Barcelona','Hombre'),
    (2,'Laura','Gomez','laura@gmail.com','666333444','1998-09-20','Madrid','Mujer'),
    (3,'Marcus','Deks','marcusdeks@hotmail.com','663076011','1995-06-29','plaza ramon mir','Hombre'),
    (4,'Profe','MasterD','profe@masterd.es','600000000','1990-01-01',NULL,NULL);

-- Usuario administrador:  usuario = Sabbath   contraseña = MasterD1
-- Usuario normal:         usuario = Profe     contraseña = MasterD
INSERT INTO users_login VALUES
    (1,3,'Sabbath','$2y$10$0nStsm4AYoCvK.ZSc/Jzf.40BsOAmw.Lc6Cn7DiNkbA6U4/tZ32di','admin'),
    (2,4,'Profe','$2y$10$nkeUjHydmWvKdyn./EIWTeBMfu.knxmQRtW.2yBJkiQ.CKQaON/PS','user');

INSERT INTO noticias VALUES
    (1,'Tajuña rock 2026','Helloween.jpg','El mejor festival hasta la fecha, 100% metal autentico.','2026-05-01',1),
    (2,'Nueva gira de música','HuesteNegra.jpg','Los Hueste Negra lo han vuelto a hacer, destrucción y un increible espectaculo en una gira increible llena de caos y blakc metal autentico.','2026-05-03',2);

INSERT INTO conciertos (idConcierto, idUser, fecha_concierto, estilo_musica, nombre_grupo, lugar) VALUES
    (1,1,'2027-01-10','Heavy Metal','Iron Maiden','Sala Ragnarok Madrid'),
    (2,1,'2027-01-15','Power Metal','Helloween','Metal Arena Barcelona'),
    (4,1,'2026-01-25','Heavy Metal','Judas Priest','Sala Valhalla Sevilla'),
    (5,1,'2026-02-02','Black Metal','Dark Funeral','Inferno Club Valencia'),
    (6,1,'2026-02-05','Death Metal','Cannibal Corpse','Apocalypse Room Murcia'),
    (7,1,'2026-02-10','Heavy Metal','Accept','Temple of Steel Málaga'),
    (8,1,'2026-02-14','Black Metal','Hueste Negra','Dragon Hall Zaragoza'),
    (9,1,'2026-02-18','Heavy Metal','Manowar','Thunder Dome Madrid'),
    (10,1,'2026-02-22','Thrash Metal','Megadeth','Skull Arena Vigo'),
    (11,1,'2026-03-01','Black Metal','Mayhem','Frozen Darkness Oviedo'),
    (12,1,'2026-03-06','Heavy Metal','Saxon','Steel Cathedral Granada'),
    (13,1,'2026-03-15','Heavy Metal','Dio Legacy','Nightfall Club Coruña'),
    (14,1,'2026-03-20','Thrash Metal','Tankard','Warrior Hall Santander'),
    (15,1,'2026-03-25','Heavy Metal','King Diamond','Coven Theater Toledo'),
    (19,3,'2027-01-10','Heavy Metal','Iron Maiden','Sala Ragnarok Madrid'),
    (21,3,'2026-10-20','Black Metal','Hueste Negra','Sala Oscura');

SET FOREIGN_KEY_CHECKS = 1;
