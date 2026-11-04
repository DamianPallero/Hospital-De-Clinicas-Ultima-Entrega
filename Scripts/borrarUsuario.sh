#!/bin/bash

echo "======== Borrar Usuario ========"

read -p "Ingrese el nombre de usuario que desea borrar: " username
username=$(echo "$username" | tr '[:upper:]' '[:lower:]')

if id "$username" &>/dev/null; then
    sudo userdel -r "$username"
    echo "El usuario $username ha sido borrado."
else
    echo "El usuario $username no existe."
fi