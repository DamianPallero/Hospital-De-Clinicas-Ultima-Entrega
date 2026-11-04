#!/bin/bash
set -euo pipefail

PRIMARY_HOST="${PRIMARY_HOST:-db_primary}"
REPLICA_HOST="${REPLICA_HOST:-127.0.0.1}"
MYSQL_ROOT_PASSWORD="${MYSQL_ROOT_PASSWORD:-root_secure_password}"

if ! mysqladmin ping -h "$PRIMARY_HOST" -uroot -p"$MYSQL_ROOT_PASSWORD" --silent >/dev/null 2>&1; then
    echo "[failback] El nodo primario '$PRIMARY_HOST' aún no está disponible. No se reconfigurará la réplica."
    exit 1
fi

mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -h "$REPLICA_HOST" <<SQL
STOP SLAVE;
RESET SLAVE ALL;
SET GLOBAL read_only = 1;
CHANGE MASTER TO
  MASTER_HOST='${PRIMARY_HOST}',
  MASTER_USER='replica',
  MASTER_PASSWORD='replication_password',
  MASTER_AUTO_POSITION=1,
  GET_MASTER_PUBLIC_KEY=1;
START SLAVE;
SQL

if mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -h "$REPLICA_HOST" -e "SHOW SLAVE STATUS\G" 2>/dev/null | grep -E "Slave_IO_Running: Yes|Slave_SQL_Running: Yes" >/dev/null 2>&1; then
    echo "[failback] Réplica reconfigurada correctamente para seguir a '$PRIMARY_HOST'."
else
    echo "[failback] La réplica no quedó sincronizada correctamente." >&2
    exit 1
fi
