#!/bin/bash

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib/common.sh"

opc=10
fecha=$(date +"%Y-%m-%d")

function menu() {
    clear
    echo "==============================="
    echo "      GESTIÓN DE REDES         "
    echo "==============================="
    echo "1. Ver estado de la red"
    echo "2. Configurar IP"
    echo "3. Mostrar interfaces de red"
    echo "4. Diagnosticar conectividad"
    echo "5. Reiniciar servicio de red"
    echo "0. Salir"
    echo "==============================="
}

while [ $opc -ne 0 ]; do
    menu
    read -p "Ingrese una opción: " opc

    case $opc in
        1)
            run_script "verEstadoRed.sh"
            ;;
        2)
            run_script "configurarIP.sh"
            ;;
        3)
            run_script "mostrarInterfaces.sh"
            ;;
        4)
            run_script "diagnosticarConectividad.sh"
            ;;
        5)
            run_script "reiniciarServicioRed.sh"
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