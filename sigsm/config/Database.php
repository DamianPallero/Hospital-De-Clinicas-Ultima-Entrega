<?php
class Database {
    private $servidor;
    private $servidorReplica;
    private $nombreBd;
    private $nomCuenta;
    private $claveAcceso;
    private $offlinePath;
    private $modoOffline;
    public $conexion;

    public function __construct() {
        date_default_timezone_set('America/Argentina/Buenos_Aires');
        $this->servidor = getenv('DB_HOST') ?: 'db_primary';
        $this->servidorReplica = getenv('DB_REPLICA_HOST') ?: getenv('DB_FAILOVER_HOST') ?: 'db_replica';
        $this->nombreBd = getenv('DB_NAME') ?: 'sigsm';
        $this->nomCuenta = getenv('DB_USER') ?: 'sigsm_user';
        $this->claveAcceso = getenv('DB_PASS') ?: 'sigsm_secure_password';
        $this->offlinePath = __DIR__ . '/offline_fallback.sqlite';
        $this->modoOffline = getenv('DB_OFFLINE_MODE') !== false ? filter_var(getenv('DB_OFFLINE_MODE'), FILTER_VALIDATE_BOOLEAN) : true;
    }

    private function crearFallbackSqlite(): PDO {
        if (!extension_loaded('pdo_sqlite') && !class_exists('SQLite3')) {
            throw new PDOException('El modo offline requiere la extensión pdo_sqlite para funcionar.');
        }

        $pdo = new PDO('sqlite:' . $this->offlinePath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $pdo->exec('PRAGMA journal_mode = WAL;');
        $pdo->exec('PRAGMA synchronous = NORMAL;');
        $pdo->exec('PRAGMA foreign_keys = ON;');

        $sql = [
            "CREATE TABLE IF NOT EXISTS backup_configuracion (
                id_backup INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL UNIQUE,
                tipo TEXT NOT NULL,
                destino TEXT NOT NULL,
                activo INTEGER DEFAULT 1,
                fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS replicacion_estado (
                id_estado INTEGER PRIMARY KEY AUTOINCREMENT,
                server_role TEXT NOT NULL,
                master_host TEXT DEFAULT NULL,
                last_sync DATETIME DEFAULT CURRENT_TIMESTAMP,
                status TEXT DEFAULT 'healthy'
            )",
            "CREATE TABLE IF NOT EXISTS rol (
                id_rol INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_rol TEXT NOT NULL UNIQUE
            )",
            "CREATE TABLE IF NOT EXISTS usuario (
                id_usuario INTEGER PRIMARY KEY AUTOINCREMENT,
                id_rol INTEGER NOT NULL,
                nombre TEXT NOT NULL,
                apellido TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
                activo INTEGER DEFAULT 1
            )",
            "CREATE TABLE IF NOT EXISTS categoria_documento (
                id_categoria INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL,
                descripcion TEXT
            )",
            "CREATE TABLE IF NOT EXISTS documento (
                id_documento INTEGER PRIMARY KEY AUTOINCREMENT,
                id_categoria INTEGER,
                id_usuario_carga INTEGER,
                titulo TEXT NOT NULL,
                url_archivo TEXT NOT NULL,
                fecha_carga DATETIME DEFAULT CURRENT_TIMESTAMP,
                eliminado INTEGER DEFAULT 0,
                fecha_eliminacion DATETIME NULL
            )",
            "CREATE TABLE IF NOT EXISTS encuesta (
                id_encuesta INTEGER PRIMARY KEY AUTOINCREMENT,
                titulo TEXT NOT NULL,
                estado INTEGER DEFAULT 1
            )",
            "CREATE TABLE IF NOT EXISTS pregunta_encuesta (
                id_pregunta INTEGER PRIMARY KEY AUTOINCREMENT,
                id_encuesta INTEGER,
                texto_pregunta TEXT NOT NULL,
                tipo_respuesta TEXT NOT NULL
            )",
            "CREATE TABLE IF NOT EXISTS respuesta_encuesta (
                id_respuesta INTEGER PRIMARY KEY AUTOINCREMENT,
                id_pregunta INTEGER,
                respuesta_texto TEXT NOT NULL,
                fecha_respuesta DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS vehiculo (
                id_vehiculo INTEGER PRIMARY KEY AUTOINCREMENT,
                patente TEXT UNIQUE NOT NULL,
                tipo TEXT NOT NULL,
                estado TEXT DEFAULT 'Disponible'
            )",
            "CREATE TABLE IF NOT EXISTS conductor (
                id_conductor INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL,
                apellido TEXT NOT NULL,
                licencia TEXT NOT NULL,
                estado TEXT DEFAULT 'Activo'
            )",
            "CREATE TABLE IF NOT EXISTS enfermero (
                id_enfermero INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL,
                apellido TEXT NOT NULL,
                matricula TEXT UNIQUE NOT NULL,
                estado TEXT DEFAULT 'Activo'
            )",
            "CREATE TABLE IF NOT EXISTS traslado (
                id_traslado INTEGER PRIMARY KEY AUTOINCREMENT,
                id_conductor INTEGER,
                id_enfermero INTEGER,
                id_vehiculo INTEGER,
                origen TEXT NOT NULL,
                destino TEXT NOT NULL,
                fecha_hora_salida DATETIME,
                fecha_hora_llegada DATETIME NULL,
                estado_traslado TEXT DEFAULT 'Pendiente',
                necesita_oxigeno INTEGER NOT NULL,
                tipo_aislamiento TEXT NOT NULL,
                requiere_ascensor_escalera TEXT NOT NULL,
                observaciones TEXT
            )"
        ];

        foreach ($sql as $sentencia) {
            $pdo->exec($sentencia);
        }

        $pdo->exec("INSERT OR IGNORE INTO rol (id_rol, nombre_rol) VALUES
            (1, 'Administrador General'),
            (2, 'Enfermería'),
            (3, 'Transporte'),
            (4, 'IT'),
            (5, 'Médico')");

        $pdo->exec("INSERT OR IGNORE INTO usuario (id_usuario, id_rol, nombre, apellido, email, password_hash) VALUES
            (1, 1, 'Carmen', 'La Torre', 'carmenlatorre@gmail.com', '$2y$12$V2.BNjzzW2XPpHVJi8R5.O0l.gmKW9WMC5Xuxb2eqvg6tMdr7ySFS'),
            (2, 2, 'Xia', 'Wú', 'moonrabbit@gmail.com', '$2y$12$V2.BNjzzW2XPpHVJi8R5.O0l.gmKW9WMC5Xuxb2eqvg6tMdr7ySFS'),
            (3, 3, 'Sasha', 'Kiselova', 'sashakiselova@gmail.com', '$2y$12$V2.BNjzzW2XPpHVJi8R5.O0l.gmKW9WMC5Xuxb2eqvg6tMdr7ySFS'),
            (4, 5, 'Cecilia', 'Himmel', 'ceciliahimmel@gmail.com', '$2y$12$V2.BNjzzW2XPpHVJi8R5.O0l.gmKW9WMC5Xuxb2eqvg6tMdr7ySFS'),
            (5, 4, 'Alyssa', 'Ingram', 'ingramalyssa@gmail.com', '$2y$12$V2.BNjzzW2XPpHVJi8R5.O0l.gmKW9WMC5Xuxb2eqvg6tMdr7ySFS')");

        $pdo->exec("INSERT OR IGNORE INTO categoria_documento (id_categoria, nombre, descripcion) VALUES
            (1, 'Historial', 'Contiene los registros del paciente'),
            (2, 'Informe', 'Registro de una consulta específica')");

        $pdo->exec("INSERT OR IGNORE INTO documento (id_documento, id_categoria, id_usuario_carga, titulo, url_archivo) VALUES
            (1, 1, 1, 'Documento Prueba Historial', 'doc1.pdf'),
            (2, 2, 2, 'Informe Prueba Consulta', 'doc2.pdf')");

        $pdo->exec("INSERT OR IGNORE INTO encuesta (id_encuesta, titulo, estado) VALUES
            (1, 'Encuesta de Satisfacción - Servicio de Traslados', 1),
            (2, 'Encuesta de Satisfacción - Atención de Enfermería', 1)");

        $pdo->exec("INSERT OR IGNORE INTO pregunta_encuesta (id_pregunta, id_encuesta, texto_pregunta, tipo_respuesta) VALUES
            (1, 1, '¿Cómo calificaría la puntualidad del traslado?', 'escala_1_a_5'),
            (2, 1, '¿El personal fue amable y claro durante el traslado?', 'si_no'),
            (3, 2, '¿Se explicaron claramente los cuidados posteriores al procedimiento?', 'escala_1_a_5')");

        $pdo->exec("INSERT OR IGNORE INTO respuesta_encuesta (id_respuesta, id_pregunta, respuesta_texto) VALUES
            (1, 1, '5'),
            (2, 2, 'Sí'),
            (3, 3, '4')");

        $pdo->exec("INSERT OR IGNORE INTO vehiculo (id_vehiculo, patente, tipo, estado) VALUES
            (1, 'ABC123', 'Ambulancia Básica', 'Disponible'),
            (2, 'XYZ789', 'Vehículo de Apoyo', 'En Traslado')");

        $pdo->exec("INSERT OR IGNORE INTO conductor (id_conductor, nombre, apellido, licencia, estado) VALUES
            (1, 'Luana', 'Moreira', 'D-123456', 'Activo'),
            (2, 'Valentina', 'Barragán', 'D-654321', 'Activo'),
            (3, 'Marie-Simone', 'Laurent', 'D-987654', 'De Licencia')");

        $pdo->exec("INSERT OR IGNORE INTO enfermero (id_enfermero, nombre, apellido, matricula, estado) VALUES
            (1, 'Inés', 'Allard', 'ENF-001', 'Activo'),
            (2, 'Irene', 'Asteri', 'ENF-002', 'Activo'),
            (3, 'Engel', 'Hahn', 'ENF-003', 'Activo')");

        $pdo->exec("INSERT OR IGNORE INTO traslado (id_traslado, id_conductor, id_enfermero, id_vehiculo, origen, destino, fecha_hora_salida, fecha_hora_llegada, estado_traslado, necesita_oxigeno, tipo_aislamiento, requiere_ascensor_escalera, observaciones) VALUES
            (1, 1, 1, 1, 'Hospital de Clínicas - Emergencia', 'Centro de Diálisis Norte', '2023-10-25 08:00:00', '2023-10-25 08:45:00', 'Completado', 0, 'Ninguno', 'Ascensor', 'Paciente estable, requiere silla de ruedas.'),
            (2, 2, 2, 2, 'Hospital de Clínicas - UTI', 'Clínica Imaging S.A.', '2023-10-26 10:00:00', NULL, 'En Ruta', 1, 'Contacto', 'Camilla y Ascensor', 'Paciente con aislamiento de contacto y soporte de oxígeno a 2L/min. Prioridad alta.')");

        return $pdo;
    }

    private function conectarConHost(string $host): PDO {
        $pdo = new PDO(
            "mysql:host={$host};dbname={$this->nombreBd};charset=utf8mb4",
            $this->nomCuenta,
            $this->claveAcceso,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        $pdo->exec("SET NAMES utf8mb4");
        $pdo->exec("SET CHARACTER SET utf8mb4");
        $pdo->exec("SET time_zone = 'America/Argentina/Buenos_Aires'");

        return $pdo;
    }

    public function getConnection() {
        $this->conexion = null;
        $hosts = [$this->servidor, $this->servidorReplica];

        foreach (array_unique(array_filter($hosts, fn($h) => $h !== '')) as $host) {
            try {
                $this->conexion = $this->conectarConHost($host);
                return $this->conexion;
            } catch (PDOException $excepcion) {
                $this->conexion = null;
            }
        }

        if ($this->modoOffline) {
            $this->conexion = $this->crearFallbackSqlite();
            return $this->conexion;
        }

        throw new PDOException('Error de conexión a la base de datos.');
    }
}