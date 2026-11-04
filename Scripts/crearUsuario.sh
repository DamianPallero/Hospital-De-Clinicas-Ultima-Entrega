#!/bin/bash

echo "===== Crear Usuario ====="

read -p "Ingrese el nombre de usuario que desea crear: " username
username=$(echo "$username" | tr '[:upper:]' '[:lower:]')

if id "$username" &>/dev/null; then
    echo "El usuario $username ya existe."
else
    sudo useradd -m "$username"
    echo "El usuario $username ha sido creado."
fi