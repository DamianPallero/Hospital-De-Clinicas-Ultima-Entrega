#!/bin/bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib/backup.sh"

require_backup_role

echo "===================="
echo "  CONFIGURAR RESPALDO AUTOMÁTICO  "
echo "===================="

read -p "Ruta absoluta del script a ejecutar (ej. /home/usuario/Scripts/enviarRespaldoRemoto.sh): " SCRIPT_PATH
SCRIPT_PATH="${SCRIPT_PATH//\\//}"

if [ ! -f "$SCRIPT_PATH" ]; then
  echo "Error: El script '$SCRIPT_PATH' no existe."
  exit 1
fi

echo
echo "Define la programación cron:"
read -p "Minuto (0-59) [0]: " CRON_MIN
read -p "Hora (0-23) [2]: " CRON_HOUR
read -p "Día del mes (1-31) [*]: " CRON_DAY
read -p "Mes (1-12) [*]: " CRON_MONTH
read -p "Día de la semana (0-7, Sun=0 o 7) [*]: " CRON_WEEKDAY

CRON_MIN="${CRON_MIN:-0}"
CRON_HOUR="${CRON_HOUR:-2}"
CRON_DAY="${CRON_DAY:-*}"
CRON_MONTH="${CRON_MONTH:-*}"
CRON_WEEKDAY="${CRON_WEEKDAY:-*}"

configure_backup_cron "$SCRIPT_PATH"

echo
echo "Programación de respaldos configurada exitosamente."
echo "Puede verificarla con: crontab -l"