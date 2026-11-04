#!/bin/bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
SCRIPTS_DIR="$PROJECT_ROOT/Scripts"

if [[ ! -d "$SCRIPTS_DIR" ]]; then
    echo "No existe el directorio de utilidades: $SCRIPTS_DIR" >&2
    exit 1
fi

case "${1:-}" in
    usuarios)
        bash "$SCRIPTS_DIR/menuUsuarios.sh"
        ;;
    grupos)
        bash "$SCRIPTS_DIR/menuGrupos.sh"
        ;;
    respaldos)
        bash "$SCRIPTS_DIR/menuRespaldos.sh"
        ;;
    redes)
        bash "$SCRIPTS_DIR/menuRedes.sh"
        ;;
    bd)
        bash "$SCRIPTS_DIR/menuBasesDatos.sh"
        ;;
    firewall)
        bash "$SCRIPTS_DIR/menuFirewall.sh"
        ;;
    logs)
        bash "$SCRIPTS_DIR/menuLogs.sh"
        ;;
    docker)
        bash "$SCRIPTS_DIR/menuDocker.sh"
        ;;
    principal)
        bash "$SCRIPTS_DIR/mainMenu.sh"
        ;;
    *)
        echo "Uso: $0 {principal|usuarios|grupos|respaldos|redes|bd|firewall|logs|docker}"
        exit 1
        ;;
esac
