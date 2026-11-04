#!/bin/bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib/backup.sh"

require_backup_role

echo "===================="
echo "  RESTAURAR RESPALDO DE BD  "
echo "===================="

ls -l "$BACKUP_BD_DIR"/*.sql 2>/dev/null || echo "No hay respaldos disponibles."
read -p "Ingrese el nombre del archivo de respaldo a restaurar (con extensión .sql): " respaldo
restore_database_backup "$BACKUP_BD_DIR/$respaldo"