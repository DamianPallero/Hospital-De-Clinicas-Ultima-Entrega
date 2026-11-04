#!/bin/bash

echo "===== Eliminar Base de Datos ====="

read -p "Ingrese el nombre de la base de datos a eliminar: " db_name

if [ -z "$db_name" ]; then
    echo "Debe ingresar un nombre de base de datos."
    exit 1
fi

mysql -uroot -p -e "DROP DATABASE IF EXISTS $db_name;"

echo "La base de datos '$db_name' fue eliminada."
