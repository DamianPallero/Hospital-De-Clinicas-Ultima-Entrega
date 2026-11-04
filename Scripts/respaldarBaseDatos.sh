#!/bin/bash

echo "===== Respaldar Base de Datos ====="

read -p "Ingrese el nombre de la base de datos a respaldar: " db_name

if [ -z "$db_name" ]; then
    echo "Debe ingresar un nombre de base de datos."
    exit 1
fi

fecha=$(date +"%Y-%m-%d_%H%M%S")
output_file="${db_name}_backup_${fecha}.sql"

echo "Se solicitará la contraseña de MySQL para realizar el volcado."
mysqldump -uroot -p "$db_name" > "$output_file"
if [ $? -eq 0 ]; then
    echo "Respaldo completado: $output_file"
else
    echo "Error al crear el respaldo de la base de datos '$db_name'."
    # Remove possibly empty/partial file
    [ -f "$output_file" ] && rm -f "$output_file"
    exit 1
fi
