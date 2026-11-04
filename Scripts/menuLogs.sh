#!/bin/bash

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib/common.sh"

opc=10
fecha=$(date +"%Y-%m-%d")

function menu() {
    clear
    echo "==============================="
    echo "      GESTIÓN DE LOGS          "
    echo "==============================="
    echo "1. Ver logs del sistema"
    echo "2. Buscar en logs"
    echo "3. Vaciar logs"
    echo "4. Configurar rotación de logs"
    echo "5. Monitorear logs en vivo"
    echo "0. Salir"
    echo "==============================="
}

while [ $opc -ne 0 ]; do
    menu
    read -p "Ingrese una opción: " opc

    case $opc in
        1)
            run_script "verLogsSistema.sh"
            ;;
        2)
            run_script "buscarEnLogs.sh"
            ;;
        3)
            run_script "vaciarLogs.sh"
            ;;
        4)
            run_script "configurarRotacionLogs.sh"
            ;;
        5)
            run_script "monitorearLogsEnVivo.sh"
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