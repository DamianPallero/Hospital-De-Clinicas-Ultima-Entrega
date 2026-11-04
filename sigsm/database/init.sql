SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE DATABASE IF NOT EXISTS sigsm;

USE sigsm;

CREATE USER IF NOT EXISTS 'replica'@'%' IDENTIFIED BY 'replication_password';
GRANT REPLICATION SLAVE, REPLICATION CLIENT ON *.* TO 'replica'@'%';
FLUSH PRIVILEGES;

CREATE TABLE IF NOT EXISTS backup_configuracion (
id_backup INT PRIMARY KEY AUTO_INCREMENT,
nombre VARCHAR(100) NOT NULL UNIQUE,
tipo VARCHAR(30) NOT NULL,
destino VARCHAR(255) NOT NULL,
activo TINYINT(1) DEFAULT 1,
fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS replicacion_estado (
id_estado INT PRIMARY KEY AUTO_INCREMENT,
server_role VARCHAR(20) NOT NULL,
master_host VARCHAR(255) DEFAULT NULL,
last_sync DATETIME DEFAULT CURRENT_TIMESTAMP,
status VARCHAR(20) DEFAULT 'healthy'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO replicacion_estado (server_role, master_host, status) VALUES
('master', 'db_primary', 'healthy')
ON DUPLICATE KEY UPDATE server_role = VALUES(server_role), master_host = VALUES(master_host), status = VALUES(status);

CREATE TABLE rol (
id_rol INT PRIMARY KEY AUTO_INCREMENT,
nombre_rol VARCHAR(255) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuario (
id_usuario INT PRIMARY KEY AUTO_INCREMENT,
id_rol INT NOT NULL,
nombre VARCHAR(50) NOT NULL,
apellido VARCHAR(50) NOT NULL,
email VARCHAR(254) NOT NULL UNIQUE,
password_hash VARCHAR(255) NOT NULL,
fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
activo TINYINT(1) DEFAULT 1,
FOREIGN KEY (id_rol) REFERENCES rol(id_rol) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categoria_documento (
id_categoria INT PRIMARY KEY AUTO_INCREMENT,
nombre VARCHAR(255) NOT NULL,
descripcion TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE documento (
id_documento INT PRIMARY KEY AUTO_INCREMENT,
id_categoria INT,
id_usuario_carga INT,
titulo VARCHAR(255) NOT NULL,
url_archivo VARCHAR(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
fecha_carga DATETIME DEFAULT CURRENT_TIMESTAMP,
eliminado TINYINT(1) DEFAULT 0,
fecha_eliminacion DATETIME NULL,
FOREIGN KEY (id_categoria) REFERENCES categoria_documento(id_categoria) ON DELETE RESTRICT,
FOREIGN KEY (id_usuario_carga) REFERENCES usuario(id_usuario) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO rol (nombre_rol) VALUES
('Administrador General'),
('Enfermería'),
('Transporte'),
('IT'),
('Médico');

INSERT INTO usuario (id_rol, nombre, apellido, email, password_hash) VALUES
('1', 'Carmen', 'La Torre', 'carmenlatorre@gmail.com', '$2y$12$V2.BNjzzW2XPpHVJi8R5.O0l.gmKW9WMC5Xuxb2eqvg6tMdr7ySFS'),
('2','Xia', 'Wú', 'moonrabbit@gmail.com', '$2y$12$V2.BNjzzW2XPpHVJi8R5.O0l.gmKW9WMC5Xuxb2eqvg6tMdr7ySFS'),
('3', 'Sasha', 'Kiselova', 'sashakiselova@gmail.com', '$2y$12$V2.BNjzzW2XPpHVJi8R5.O0l.gmKW9WMC5Xuxb2eqvg6tMdr7ySFS'),
('5', 'Cecilia', 'Himmel', 'ceciliahimmel@gmail.com', '$2y$12$V2.BNjzzW2XPpHVJi8R5.O0l.gmKW9WMC5Xuxb2eqvg6tMdr7ySFS'),
('4', 'Alyssa', 'Ingram', 'ingramalyssa@gmail.com', '$2y$12$V2.BNjzzW2XPpHVJi8R5.O0l.gmKW9WMC5Xuxb2eqvg6tMdr7ySFS');

INSERT INTO categoria_documento (nombre, descripcion) VALUES
('Historial', 'Contiene los registros del paciente'),
('Informe', 'Registro de una consulta específica');

INSERT INTO documento (id_categoria, id_usuario_carga, titulo, url_archivo) VALUES
(1, 1, 'Documento Prueba Historial', 'https://ejemplo.com/doc1.pdf'),
(2, 2, 'Informe Prueba Consulta', 'https://ejemplo2.com/doc2.pdf');

CREATE TABLE encuesta (
id_encuesta INT PRIMARY KEY AUTO_INCREMENT,
titulo VARCHAR(100) NOT NULL,
estado TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pregunta_encuesta (
id_pregunta INT PRIMARY KEY AUTO_INCREMENT,
id_encuesta INT,
texto_pregunta TEXT NOT NULL,
tipo_respuesta VARCHAR(100) NOT NULL,
FOREIGN KEY (id_encuesta) REFERENCES encuesta(id_encuesta) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE respuesta_encuesta (
id_respuesta INT PRIMARY KEY AUTO_INCREMENT,
id_pregunta INT,
respuesta_texto TEXT NOT NULL,
fecha_respuesta DATETIME DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (id_pregunta) REFERENCES pregunta_encuesta(id_pregunta) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO encuesta (titulo, estado) VALUES
('Encuesta de Satisfacción - Servicio de Traslados', 1),
('Encuesta de Satisfacción - Atención de Enfermería', 1);

INSERT INTO pregunta_encuesta (id_encuesta, texto_pregunta, tipo_respuesta) VALUES
(1, '¿Cómo calificaría la puntualidad del traslado?', 'escala_1_a_5'),
(1, '¿El personal fue amable y claro durante el traslado?', 'si_no'),
(2, '¿Se explicaron claramente los cuidados posteriores al procedimiento?', 'escala_1_a_5');

INSERT INTO respuesta_encuesta (id_pregunta, respuesta_texto) VALUES
(1, '5'),
(2, 'Sí'),
(3, '4');

CREATE TABLE vehiculo (
id_vehiculo INT PRIMARY KEY AUTO_INCREMENT,
patente VARCHAR(8) UNIQUE NOT NULL,
tipo VARCHAR(100) NOT NULL,
estado VARCHAR(20) DEFAULT 'Disponible'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE conductor (
id_conductor INT PRIMARY KEY AUTO_INCREMENT,
nombre VARCHAR(50) NOT NULL,
apellido VARCHAR(50) NOT NULL,
licencia VARCHAR(30) NOT NULL,
estado VARCHAR(20) DEFAULT 'Activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE enfermero (
id_enfermero INT PRIMARY KEY AUTO_INCREMENT,
nombre VARCHAR(50) NOT NULL,
apellido VARCHAR(50) NOT NULL,
matricula VARCHAR(20) UNIQUE NOT NULL,
estado VARCHAR(20) DEFAULT 'Activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE traslado (
id_traslado INT PRIMARY KEY AUTO_INCREMENT,
id_conductor INT,
id_enfermero INT,
id_vehiculo INT,
origen VARCHAR(255) NOT NULL,
destino VARCHAR(255) NOT NULL,
fecha_hora_salida DATETIME,
fecha_hora_llegada DATETIME NULL,
estado_traslado VARCHAR(20) DEFAULT 'Pendiente',
necesita_oxigeno TINYINT(1) NOT NULL,
tipo_aislamiento VARCHAR(255) NOT NULL,
requiere_ascensor_escalera VARCHAR(255) NOT NULL,
observaciones TEXT,
FOREIGN KEY (id_conductor) REFERENCES conductor(id_conductor) ON DELETE RESTRICT,
FOREIGN KEY (id_enfermero) REFERENCES enfermero(id_enfermero) ON DELETE RESTRICT,
FOREIGN KEY (id_vehiculo) REFERENCES vehiculo(id_vehiculo) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO vehiculo (patente, tipo, estado) VALUES
('ABC123', 'Ambulancia Básica', 'Disponible'),
('XYZ789', 'Vehículo de Apoyo', 'En Traslado');

INSERT INTO conductor (nombre, apellido, licencia, estado) VALUES
('Luana', 'Moreira', 'D-123456', 'Activo'),
('Valentina', 'Barragán', 'D-654321', 'Activo'),
('Marie-Simone', 'Laurent', 'D-987654', 'De Licencia');

INSERT INTO enfermero (nombre, apellido, matricula, estado) VALUES
('Inés', 'Allard', 'ENF-001', 'Activo'),
('Irene', 'Asteri', 'ENF-002', 'Activo'),
('Engel', 'Hahn', 'ENF-003', 'Activo');

INSERT INTO traslado (id_conductor, id_enfermero, id_vehiculo, origen, destino, fecha_hora_salida, fecha_hora_llegada, estado_traslado, necesita_oxigeno, tipo_aislamiento, requiere_ascensor_escalera, observaciones) VALUES
(1, 1, 1, 'Hospital de Clínicas - Emergencia', 'Centro de Diálisis Norte', '2023-10-25 08:00:00', '2023-10-25 08:45:00', 'Completado', 0, 'Ninguno', 'Ascensor', 'Paciente estable, requiere silla de ruedas.'),
(2, 2, 2, 'Hospital de Clínicas - UTI', 'Clínica Imaging S.A.', '2023-10-26 10:00:00', NULL, 'En Ruta', 1, 'Contacto', 'Camilla y Ascensor', 'Paciente con aislamiento de contacto y soporte de oxígeno a 2L/min. Prioridad alta.');
