#!/bin/bash

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib/common.sh"
source "$SCRIPT_DIR/lib/backup.sh"

opc=10
fecha=$(date +%Y-%m-%d)

function crearRespaldoBD() {
    create_database_backup
}

function crearRespaldoLogs() {
    create_logs_backup
}

function restaurarRespaldoBD() {
    local archivo
    echo "Respaldos disponibles:"
    ls -l "$BACKUP_BD_DIR"/*.sql 2>/dev/null || echo "No hay respaldos de BD."
    read -p "Ingrese el nombre del archivo SQL a restaurar: " archivo
    if [[ -z "$archivo" ]]; then
        echo "Debe indicar un archivo."
        return 1
    fi
    restore_database_backup "$BACKUP_BD_DIR/$archivo"
}

function restaurarRespaldoLogs() {
    local archivo
    echo "Respaldos disponibles:"
    ls -l "$BACKUP_LOGS_DIR"/*.tar.gz 2>/dev/null || echo "No hay respaldos de logs."
    read -p "Ingrese el nombre del archivo .tar.gz a restaurar: " archivo
    if [[ -z "$archivo" ]]; then
        echo "Debe indicar un archivo."
        return 1
    fi
    restore_logs_backup "$BACKUP_LOGS_DIR/$archivo"
}

function eliminarRespaldo() {
    local tipo
    echo "1) Respaldo de BD"
    echo "2) Respaldo de logs"
    read -p "Seleccione el tipo: " tipo
    if [[ "$tipo" == "1" ]]; then
        ls -l "$BACKUP_BD_DIR"/*.sql 2>/dev/null || echo "No hay respaldos de BD."
        read -p "Ingrese el nombre del archivo a eliminar: " archivo
        rm -f "$BACKUP_BD_DIR/$archivo"
    elif [[ "$tipo" == "2" ]]; then
        ls -l "$BACKUP_LOGS_DIR"/*.tar.gz 2>/dev/null || echo "No hay respaldos de logs."
        read -p "Ingrese el nombre del archivo a eliminar: " archivo
        rm -f "$BACKUP_LOGS_DIR/$archivo"
    else
        echo "Opción inválida."
    fi
}

function listarRespaldos() {
    list_backups
}

function configurarRespaldoAutomatico() {
    read -p "Ruta absoluta del script a ejecutar (ej. /home/usuario/Scripts/enviarRespaldoRemoto.sh): " script_path
    script_path="${script_path//\\//}"
    if [[ ! -f "$script_path" ]]; then
        echo "El script '$script_path' no existe."
        return 1
    fi
    CRON_MIN="${CRON_MIN:-0}"
    CRON_HOUR="${CRON_HOUR:-2}"
    CRON_DAY="${CRON_DAY:-*}"
    CRON_MONTH="${CRON_MONTH:-*}"
    CRON_WEEKDAY="${CRON_WEEKDAY:-*}"
    configure_backup_cron "$script_path"
}

function enviarRespaldoRemoto() {
    send_remote_backup
}

function menu() {
    clear
    echo "===================="
    echo "  GESTIÓN DE RESPALDOS  "
    echo "===================="
    echo "1. Crear Respaldo de BD"
    echo "2. Crear Respaldo de Logs del Sistema"
    echo "3. Restaurar Respaldo de BD"
    echo "4. Restaurar Respaldo de Logs del Sistema"
    echo "5. Eliminar Respaldo"
    echo "6. Listar Respaldos"
    echo "7. Configurar Respaldo Automático"
    echo "8. Enviar Respaldo a Ubicación Remota"
    echo "0. Salir"
    echo "===================="
}

while [ $opc -ne 0 ]; do
    menu
    read -p "Ingrese una opción: " opc

    case $opc in
        1)
            crearRespaldoBD
            ;;
        2)
            crearRespaldoLogs
            ;;
        3)
            restaurarRespaldoBD
            ;;
        4)
            restaurarRespaldoLogs
            ;;
        5)
            eliminarRespaldo
            ;;
        6)
            listarRespaldos
            ;;
        7)
            configurarRespaldoAutomatico
            ;;
        8)
            enviarRespaldoRemoto
            ;;
        0)
            echo "Saliendo del menú..."
            ;;
        *)
            echo "Opción inválida. Intente nuevamente."
            ;;
    esac

    read -p "Presione Enter para continuar..."
done