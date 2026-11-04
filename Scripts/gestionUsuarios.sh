#!/bin/bash

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib/common.sh"

opc=10
date=$(date +"%Y-%m-%d")

function menuUsuarios(){
    clear
    echo "=============================================="
    echo "             Gestión de Usuarios              "
    echo "=============================================="
    echo "1 - Crear Usuario"
    echo "2 - Borrar Usuario"
    echo "3 - Listar Usuarios"
    echo "4 - Buscar Usuario"
    echo "5 - Cambiar Contraseña de Usuario"
    echo "6 - Bloquear Usuario"
    echo "7 - Desbloquear Usuario"
    echo "8 - Volver al Menú Principal"
    echo "=============================================="
    echo "Seleccione una opción: "
}

while [ $opc -ne 8 ]; do
    menuUsuarios
    read opc
    case $opc in
        1) run_script "crearUsuario.sh";;
        2) run_script "borrarUsuario.sh";;
        3) run_script "listarUsuarios.sh";;
        4) run_script "buscarUsuario.sh";;
        5) run_script "cambiarContraseña.sh";;
        6) run_script "bloquearUsuario.sh";;
        7) run_script "desbloquearUsuario.sh";;
        8) echo "Volviendo al Menú Principal...";;
        *) echo "Opción inválida. Intente nuevamente.";;
    esac
done