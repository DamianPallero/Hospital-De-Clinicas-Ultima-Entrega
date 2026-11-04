#!/bin/bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib/backup.sh"

require_backup_role

echo "===================="
echo "  CREAR RESPALDO DE LOGS  "
echo "===================="
create_logs_backup