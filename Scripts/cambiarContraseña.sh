#!/bin/bash

echo "======== Cambiar Contraseña de Usuario ========"

read -p "Ingrese el nombre de usuario del que desea cambiar la contraseña: " username
username=$(echo "$username" | tr '[:upper:]' '[:lower:]')

if id "$username" &>/dev/null; then
    sudo passwd "$username"
    echo "La contraseña del usuario $username ha sido cambiada."
else
    echo "El usuario $username no existe."
fi