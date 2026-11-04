#!/bin/bash
set -euo pipefail

PRIMARY_HOST="${PRIMARY_HOST:-db_primary}"
MYSQL_ROOT_PASSWORD="${MYSQL_ROOT_PASSWORD:-root_secure_password}"

while true; do
    if ! mysqladmin ping -h "$PRIMARY_HOST" -uroot -p"$MYSQL_ROOT_PASSWORD" --silent >/dev/null 2>&1; then
        echo "[failover] Primary unavailable. Checking replica promotion..."

        if mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -h 127.0.0.1 -e "SELECT @@read_only;" 2>/dev/null | tail -n +2 | grep -q '1'; then
            mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -h 127.0.0.1 -e "STOP SLAVE; RESET SLAVE ALL; SET GLOBAL read_only = 0;"
            echo "[failover] Replica promoted to writable primary."
        else
            echo "[failover] Replica already writable or promotion not needed."
        fi
    fi

    sleep 10
done
