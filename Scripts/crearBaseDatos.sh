#!/bin/bash

echo "===== Crear Base de Datos ====="

read -p "Ingrese el nombre de la base de datos: " db_name

if [ -z "$db_name" ]; then
    echo "El nombre de la base de datos no puede estar vacío."
    exit 1
fi

mysql -uroot -p -e "CREATE DATABASE IF NOT EXISTS $db_name;"

if [ $? -eq 0 ]; then
    echo "La base de datos '$db_name' fue creada correctamente."
else
    echo "No se pudo crear la base de datos '$db_name'."
fi
