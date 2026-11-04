#!/bin/bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKUP_LIB=""
for candidate in \
    "$SCRIPT_DIR/../Scripts/lib/backup.sh" \
    "$SCRIPT_DIR/Scripts/lib/backup.sh" \
    "$SCRIPT_DIR/../../Scripts/lib/backup.sh"; do
    if [[ -f "$candidate" ]]; then
        BACKUP_LIB="$candidate"
        break
    fi
done

if [[ -z "$BACKUP_LIB" ]]; then
    echo "No se encontró Scripts/lib/backup.sh desde '$SCRIPT_DIR'." >&2
    exit 1
fi

source "$BACKUP_LIB"

export BACKUP_ROLE="${BACKUP_ROLE:-Administrador General}"
export BACKUP_ROOT="${BACKUP_ROOT:-/root}"
export BACKUP_BD_DIR="${BACKUP_BD_DIR:-${BACKUP_ROOT}/respaldos_bd}"
export BACKUP_LOGS_DIR="${BACKUP_LOGS_DIR:-${BACKUP_ROOT}/respaldos_logs}"
export MYSQL_BACKUP_HOST="${MYSQL_BACKUP_HOST:-db_replica}"
export MYSQL_HOST="${MYSQL_HOST:-${MYSQL_BACKUP_HOST:-db_replica}}"
export MYSQL_PORT="${MYSQL_PORT:-3306}"
export MYSQL_USER="${MYSQL_USER:-sigsm_user}"
export MYSQL_PASSWORD="${MYSQL_PASSWORD:-sigsm_secure_password}"
export MYSQL_DATABASE="${MYSQL_DATABASE:-sigsm}"

ensure_backup_dirs
create_database_backup
create_logs_backup

echo "Backup automático completado: $(ls -1 "${BACKUP_BD_DIR}"/*.sql | tail -n 1) y $(ls -1 "${BACKUP_LOGS_DIR}"/*.tar.gz | tail -n 1)"
