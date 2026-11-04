#!/bin/bash

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib/common.sh"

opc=10
fecha=$(date +"%Y-%m-%d")

function menu(){
    echo "=============================================="
    echo "             Menú Principal                   "
    echo "=============================================="
    echo "1 - Gestión de Usuarios"
    echo "2 - Gestión de Grupos"
    echo "3 - Gestión de Respaldos"
    echo "4 - Gestión de Redes"
    echo "5 - Gestión de Bases de Datos"
    echo "6 - Gestión de Firewall"
    echo "7 - Gestión de Logs del Sistema"
    echo "8 - Gestión de Docker"
    echo "9 - Salir"
    echo "=============================================="
    echo "Seleccione una opción: "
}

while [ $opc -ne 9 ]; do
    menu
    read opc
    case $opc in
        1) bash "$SCRIPT_DIR/menuUsuarios.sh";;
        2) bash "$SCRIPT_DIR/menuGrupos.sh";;
        3) bash "$SCRIPT_DIR/menuRespaldos.sh";;
        4) bash "$SCRIPT_DIR/menuRedes.sh";;
        5) bash "$SCRIPT_DIR/menuBasesDatos.sh";;
        6) bash "$SCRIPT_DIR/menuFirewall.sh";;
        7) bash "$SCRIPT_DIR/menuLogs.sh";;
        8) bash "$SCRIPT_DIR/menuDocker.sh";;
        9) echo "Saliendo...";;
        *) echo "Opción inválida. Intente nuevamente.";;
    esac
done