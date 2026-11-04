#!/bin/bash

echo "===================="
echo "    BORRAR GRUPO  "
echo "===================="

read -p "Ingrese el nombre del grupo a borrar: " grupoUsuario
grupo=$(echo "$grupoUsuario" | tr '[:upper:]' '[:lower:]')

if [ $(getent group "$grupo") ]; then
    sudo groupdel "$grupo"
    if [ $? -eq 0 ]; then
        echo "Grupo '$grupo' borrado exitosamente."
    else
        echo "Error al borrar el grupo '$grupo'."
    fi
else
    echo "El grupo '$grupo' no existe."
fi