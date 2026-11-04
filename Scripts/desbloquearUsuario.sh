#!/bin/bash

echo "======== Desbloquear Usuario ========"

read -p "Ingrese el nombre de usuario que desea desbloquear: " username
username=$(echo "$username" | tr '[:upper:]' '[:lower:]')

if id "$username" &>/dev/null; then
    usermod -U "$username"
    echo "El usuario $username ha sido desbloqueado."
else
    echo "El usuario $username no existe."
fi