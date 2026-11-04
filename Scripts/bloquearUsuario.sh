#!/bin/bash

echo "======== Bloquear Usuario ========"

read -p "Ingrese el nombre de usuario que desea bloquear: " username
username=$(echo "$username" | tr '[:upper:]' '[:lower:]')

if id "$username" &>/dev/null; then
    usermod -L "$username"
    echo "El usuario $username ha sido bloqueado."
else
    echo "El usuario $username no existe."
fi