#!/bin/bash

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib/common.sh"

opc=10
fecha=$(date +"%Y-%m-%d")

function menu() {
    clear
    echo "===================================="
    echo "         GESTIÓN DE DOCKER         "
    echo "===================================="
    echo "1. Ver estado de Docker"
    echo "2. Listar contenedores"
    echo "3. Iniciar contenedor"
    echo "4. Detener contenedor"
    echo "5. Reiniciar contenedor"
    echo "6. Listar imágenes"
    echo "7. Eliminar contenedor"
    echo "0. Salir"
    echo "===================================="
}

while [ $opc -ne 0 ]; do
    menu
    read -p "Ingrese una opción: " opc

    case $opc in
        1)
            run_script "estadoDocker.sh"
            ;;
        2)
            run_script "listarContenedores.sh"
            ;;
        3)
            run_script "iniciarContenedor.sh"
            ;;
        4)
            run_script "detenerContenedor.sh"
            ;;
        5)
            run_script "reiniciarContenedor.sh"
            ;;
        6)
            run_script "listarImagenes.sh"
            ;;
        7)
            run_script "eliminarContenedor.sh"
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