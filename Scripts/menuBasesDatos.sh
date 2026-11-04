#!/bin/bash

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib/common.sh"

opc=10
fecha=$(date +"%Y-%m-%d")

function menu() {
    clear
    echo "===================================="
    echo "      GESTIÓN DE BASES DE DATOS     "
    echo "===================================="
    echo "1. Crear Base de Datos"
    echo "2. Listar Bases de Datos"
    echo "3. Aplicar DDL (crear esquema y tablas desde DDL.sql)"
    echo "4. Respaldar Base de Datos"
    echo "5. Eliminar Base de Datos"
    echo "0. Salir"
    echo "===================================="
}

while [ $opc -ne 0 ]; do
    menu
    read -p "Ingrese una opción: " opc

    case $opc in
        1)
            run_script "crearBaseDatos.sh"
            ;;
        2)
            run_script "listarBasesDatos.sh"
            ;;
        3)
            run_script "aplicarEsquema.sh"
            ;;
        4)
            run_script "respaldarBaseDatos.sh"
            ;;
        5)
            run_script "eliminarBaseDatos.sh"
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