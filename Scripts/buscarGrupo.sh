#!/bin/bash

echo "===================="
echo "    BUSCAR GRUPO  "
echo "===================="

read -p "Ingrese el nombre del grupo a buscar: " grupoUsuario
grupo=$(echo "$grupoUsuario" | tr '[:upper:]' '[:lower:]')

if [ $(getent group "$grupo") ]; then
    echo "El grupo '$grupo' existe."
else
    echo "El grupo '$grupo' no existe."
fi