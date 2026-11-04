#!/bin/bash

echo "===== Restaurar Base de Datos ====="

read -p "Ingrese el nombre de la base de datos a restaurar: " db_name
read -p "Ingrese la ruta del archivo SQL: " archivo_sql

if [ -z "$db_name" ] || [ -z "$archivo_sql" ]; then
    echo "Debe ingresar la base de datos y la ruta del archivo SQL."
    exit 1
fi

mysql -uroot -p "$db_name" < "$archivo_sql"

echo "La base de datos '$db_name' fue restaurada desde '$archivo_sql'."
