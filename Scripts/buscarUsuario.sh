#!/bin/bash

echo "======== Buscar Usuario ========"

read -p "Ingrese el nombre de usuario que desea buscar: " username
username=$(echo "$username" | tr '[:upper:]' '[:lower:]')

if id "$username" &>/dev/null; then
    echo "El usuario $username existe."
else
    echo "El usuario $username no existe."
fi