S.I.G.S.M. - Sistema de Gestion de Traslados de Pacientes

Proyecto Final
Programacion Full Stack
Equipo FoxWare - Polo MVD 3 MA

Sistema interno desarrollado para el Hospital de Clinicas que gestiona los traslados de pacientes dentro del circuito nacional, la documentacion clinica asociada, la gestion operativa y las encuestas de satisfaccion, cumpliendo con estandares de accesibilidad (WCAG 2.1 AA), segregacion de funciones, seguridad institucional y continuidad operativa en entornos de produccion.

INDICE

1. Descripcion
2. Modulos
3. Tecnologias
4. Requisitos
5. Instalacion
6. Uso
7. Estructura del Proyecto
8. Equipo
9. Documentacion y Base de Datos

DESCRIPCIÓN

El S.I.G.S.M. reemplaza el sistema de registros en papel utilizado actualmente por el Hospital de Clínicas, digitalizando los procesos de:
Solicitud y asignación de traslados de pacientes.
Carga, consulta y gestion segura de documentación clínica mediante un repositorio con encriptación de rutas, hash de archivos y validación de permisos.
Gestion de recursos (vehículos, conductores, enfermeros).
Encuestas anónimas de satisfacción accesibles via codigos QR con sistema de versiones, invalidación automática y protección anti-reenvío.

El sistema opera exclusivamente en la intranet hospitalaria, con acceso restringido mediante un modelo de Control de Acceso Basado en Roles (RBAC), credenciales institucionales validadas y una infraestructura de base de datos preparada para alta disponibilidad con replicas y monitoreo de failover.

MÓDULOS

1. Modulo de Documentación y Encuestas
Panel administrativo con RBAC estricto (Enfermería carga, Administrador elimina).
Gateway de Archivos Seguro: Los archivos se guardan bajo nombres hash, se almacenan en directorios protegidos y se sirven a traves de un proxy PHP que valida permisos, tipo MIME y acceso directo.
QR de Documento (Un solo uso): Genera un token temporal que se invalida automáticamente tras el primer escaneo.
QR de Encuestas (Con versiones): Se utiliza un codigo estático para impresión en paredes y se puede invalidar versiones previas desde el panel de IT, generando una nueva URL y mostrando alertas de actualización.
Protección Anti-Reenvío: Implementación de cabeceras Cache-Control, control de bfcache y protecciones front-end para evitar que el botón para ir atrás del navegador permita responder la encuesta multiples veces.
Panel de gestion documental y de respuestas con filtrado, estadísticas y acciones restringidas por rol.

2. Modulo de Traslados (Ambulancias)
Panel de gestion de solicitudes de traslado.
Formulario de alta con validación de campos clínicos y logísticos (oxigeno, aislamiento, tipo de vehículo, ascensor y observaciones).
Vista de seguimiento de estado, asignación de recursos y control de conductores, enfermeros y vehículos.
Manejo de estados operativos para traslados en curso, completados, pendientes o con prioridad especifica.

3. Modulo de Seguridad y continuidad operativa
Estructura de aplicación en contenedores Docker con PHP + Apache + MySQL.
Configuración de base de datos primaria y replica para lectura escalada y resiliencia.
Monitores de failover y failback para reconfiguración automática de la replica en caso de caída del primario.
Modo offline con respaldo en SQLite para continuidad parcial cuando la base principal no este disponible.

TECNOLOGÍAS

Backend: PHP 8.2 (Programación Orientada a Objetos, PDO para consultas preparadas, autenticación centralizada y control de sesiones).
Base de Datos: MySQL 8.0 con configuración de replica, failover y sincronización GTID.
Frontend: HTML5, JavaScript (Vanilla), Bootstrap 5.3 (Diseño responsive, accesible y mobile-first).
Servidor Web: Apache 2.4+ dentro de contenedores Docker.
Contenedores: Docker Compose con servicios web, base primaria, replica, monitor de failover y monitor de failback.
Control de Versiones: Git + GitHub.
IDE: Visual Studio Code.

REQUISITOS

Antes de comenzar, asegurate de tener instalados:
Docker Engine + Docker Compose v2.
Git.
Visual Studio Code.
Navegador moderno (Chrome, Firefox o Edge).

INSTALACIÓN

1. Clonar el repositorio
cd /ruta/de/trabajo
git clone https://github.com/foxwareenterprises/Hospital-De-Clinicas.git
cd sigsm

2. Levantar la infraestructura con Docker
Desde la raiz del proyecto, ejecuta:

docker compose up --build -d

Esto levanta los contenedores principales:
- web: aplicacion PHP + Apache
- db_primary: base de datos principal MySQL 8.0
- db_replica: replica de lectura
- failover_monitor: monitoreo y promocion de replica en caso de falla
- failback_monitor: reconexion automatica de la replica cuando el primario vuelve a estar disponible

3. Verificar el estado de los servicios
Para confirmar que los contenedores esten activos y saludables:

docker compose ps

docker compose logs -f web

La aplicación queda disponible en:
http://localhost/
http://localhost/sigsm/
http://[ip-server-dti]/
http://[ip-server-dti]/sigsm/
https://[ip-server-dti]/
https://[ip-server-dti]/sigsm/


4. Configuración de la Base de Datos
La inicialización de la base de datos se realiza automáticamente desde los scripts incluidos en la carpeta database/.
Los archivos init.sql y setup_replica.sql generan la estructura inicial, usuarios, roles y la configuración de replica.
Las variables de entorno definidas en docker-compose.yaml se encargan de conectar la aplicación con la base principal y la replica sin requerir cambios manuales en el codigo.

5. Permisos de Carpetas
La aplicación crea y prepara automáticamente las carpetas uploads/, temp_qr/ y config/ dentro del contenedor para garantizar acceso y escritura para la capa web.
La configuracion de SSL local para Apache se genera al iniciar el servicio si no existe un certificado previo.

USO

1. Accede al portal mediante: http://[ip-server-dti]/
2. Inicia sesión con las credenciales iniciales definidas en el script de inicialización de la base de datos. Las contraseñas para todos los usuarios iniciales son 123abc, utilizar 'carmenlatorre@gmail.com' como usuario, puede ver las otras credenciales en gestión de personal. Asegúrese de eliminar las credenciales iniciales.
3. Navegación por Roles:
Enfermería/Medico: Pueden ver y cargar documentos, pero no eliminarlos.
Administrador General: Control total (crear, editar, eliminar documentos y gestionar usuarios).
IT: Acceso exclusivo al panel de gestion de encuestas, QR y control de versiones.
Transporte: Redirigido automaticamente a su modulo exclusivo.
4. Generación de QR: Desde el panel principal o el modulo de IT, genera códigos QR para documentos específicos o para el portal de encuestas.
5. Gestion operativa: El panel principal permite consultar actividad del día, acceder a documentos, encuestas, respuestas y a la gestión de traslados según el rol asociado.

ESTRUCTURA DEL PROYECTO

.
├── Scripts
│   ├── agregarGrupo.sh
│   ├── agregarServicios.sh
│   ├── aplicarEsquema.sh
│   ├── bloquearIP.sh
│   ├── bloquearMac.sh
│   ├── bloquearUsuario.sh
│   ├── borrarGrupo.sh
│   ├── borrarUsuario.sh
│   ├── buscarEnLogs.sh
│   ├── buscarGrupo.sh
│   ├── buscarUsuario.sh
│   ├── cambiarContraseña.sh
│   ├── configurarIP.sh
│   ├── configurarRespaldoAutomatico.sh
│   ├── configurarRotacionLogs.sh
│   ├── crearBaseDatos.sh
│   ├── crearRespaldoBD.sh
│   ├── crearRespaldoLogs.sh
│   ├── crearUsuario.sh
│   ├── desbloquearUsuario.sh
│   ├── detenerContenedor.sh
│   ├── diagnosticarConectividad.sh
│   ├── eliminarBaseDatos.sh
│   ├── eliminarContenedor.sh
│   ├── eliminarRespaldo.sh
│   ├── enviarRespaldoRemoto.sh
│   ├── estadoDocker.sh
│   ├── gestionUsuarios.sh
│   ├── iniciarContenedor.sh
│   ├── lib
│   │   ├── backup.sh
│   │   └── common.sh
│   ├── listarBasesDatos.sh
│   ├── listarContenedores.sh
│   ├── listarGrupos.sh
│   ├── listarImagenes.sh
│   ├── listarRespaldos.sh
│   ├── listarUsuarios.sh
│   ├── mainMenu.sh
│   ├── menuBasesDatos.sh
│   ├── menuDocker.sh
│   ├── menuFirewall.sh
│   ├── menuGrupos.sh
│   ├── menuLogs.sh
│   ├── menuRedes.sh
│   ├── menuRespaldos.sh
│   ├── menuUsuarios.sh
│   ├── monitorearLogsEnVivo.sh
│   ├── mostrarInterfaces.sh
│   ├── permitirHTTP.sh
│   ├── politicaSeguridad.sh
│   ├── reiniciarContenedor.sh
│   ├── reiniciarServicioRed.sh
│   ├── respaldarBaseDatos.sh
│   ├── restaurarBaseDatos.sh
│   ├── restaurarRespaldoBD.sh
│   ├── restaurarRespaldoLogs.sh
│   ├── serviciosPermitidos.sh
│   ├── solicitudesPing.sh
│   ├── vaciarLogs.sh
│   ├── verEstadoFirewall.sh
│   ├── verEstadoRed.sh
│   ├── verificarConexionBD.sh
│   └── verLogsSistema.sh
└── sigsm
    ├── assets
    │   ├── css
    │   │   └── mobile.css
    │   └── js
    │       ├── session-warning.js
    │       └── validaciones.js
    ├── backup_task.sh
    ├── config
    │   ├── apache-custom.conf
    │   ├── Database.php
    │   ├── offline_fallback.sqlite
    │   ├── php-custom.ini
    │   └── reset_codes
    ├── controllers
    │   ├── AuthController.php
    │   ├── DocumentoController.php
    │   ├── EncuestaController.php
    │   ├── QRController.php
    │   ├── TrasladoController.php
    │   └── UsuarioController.php
    ├── database
    │   ├── failback_primary.sh
    │   ├── init.sql
    │   ├── promote_replica.sh
    │   └── setup_replica.sql
    ├── docker-compose.yaml
    ├── Dockerfile
    ├── encuestas
    │   ├── gracias.php
    │   ├── portal.php
    │   ├── procesar.php
    │   └── tomar.php
    ├── entrypoint.sh
    ├── includes
    │   ├── auth_middleware.php
    │   ├── Cache.php
    │   ├── Captcha.php
    │   ├── extend_session.php
    │   └── Security.php
    ├── index.php
    ├── install.php
    ├── models
    │   ├── Documento.php
    │   ├── Encuesta.php
    │   ├── Traslado.php
    │   └── Usuario.php
    ├── scripts
    │   ├── README.md
    │   └── run_menu.sh
    ├── temp_qr
    ├── uploads
    ├── ver_documento.php
    ├── ver_documento_qr.php
    └── views
        ├── cambiar_password.php
        ├── documentos.php
        ├── encuestas_admin.php
        ├── forgot_password.php
        ├── login.php
        ├── logout.php
        ├── qr_documentos.php
        ├── reset_password.php
        ├── traslados.php
        ├── usuarios.php
        └── ver_respuestas.php

EQUIPO

Equipo FoxWare
Desarrollado como Proyecto Final para el Polo MVD 3 MA del Bachillerato Tecnológico Bilingüe.

DOCUMENTACIÓN Y BASE DE DATOS

Los scripts DDL y DML de inicialización (init.sql) y configuración de la base de datos se encuentran dentro de la carpeta database/ del proyecto, junto con los archivos de replica, failover y restauración.
El esquema de base de datos esta diseñado para garantizar la integridad referencial, la trazabilidad de las cargas de documentos, la segregación de roles y la resiliencia operativa del sistema.
La aplicación contempla un modelo de alta disponibilidad con base principal MySQL y replica secundaria, así como mecanismos de monitorización para mantener la continuidad en caso de interrupciones del servidor primario.
