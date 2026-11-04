#!/bin/bash

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib/common.sh"

opc=10
year=$(date +%Y-%m-%d)

function menu() {
    clear
    echo "===================="
    echo "    MENU GESTIÓN DE GRUPOS  "
    echo "===================="
    echo "1. Agregar Grupo"
    echo "2. Borrar Grupo"
    echo "3. Listar Grupos"
    echo "4. Buscar Grupo"
    echo "0. Salir"
    echo "===================="
}

while [ $opc -ne 0 ]; do
    menu
    read -p "Ingrese una opción: " opc

    case $opc in
        1)
            run_script "agregarGrupo.sh"
            ;;
        2)
            run_script "borrarGrupo.sh"
            ;;
        3)
            run_script "listarGrupos.sh"
            ;;
        4)
            run_script "buscarGrupo.sh"
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