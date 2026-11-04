#!/bin/bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib/backup.sh"

require_backup_role

echo "===================="
echo "  RESTAURAR RESPALDO DE LOGS "
echo "===================="

ls -l "$BACKUP_LOGS_DIR"/*.tar.gz 2>/dev/null || echo "No hay respaldos disponibles."
read -p "Ingrese el nombre del archivo de respaldo a restaurar (con extensión .tar.gz): " respaldo
restore_logs_backup "$BACKUP_LOGS_DIR/$respaldo"