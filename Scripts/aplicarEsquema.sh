#!/bin/bash

echo "===== Aplicar DDL (crear esquema y tablas) ====="

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DDL_FILE="$SCRIPT_DIR/DDL.sql"

if [ ! -f "$DDL_FILE" ]; then
    echo "No se encontró DDL.sql en $SCRIPT_DIR"
    exit 1
fi

echo "Advertencia: DDL.sql puede contener instrucciones destructivas (DROP DATABASE)."
read -p "¿Desea continuar y ejecutar DDL.sql? (s/N): " confirm
confirm=${confirm,,} # to lower
if [[ "$confirm" != "s" && "$confirm" != "si" ]]; then
    echo "Operación cancelada."
    exit 0
fi

mysql -uroot -p < "$DDL_FILE"
if [ $? -eq 0 ]; then
    echo "DDL aplicado correctamente."
else
    echo "Ocurrió un error al aplicar el DDL."
    exit 1
fi