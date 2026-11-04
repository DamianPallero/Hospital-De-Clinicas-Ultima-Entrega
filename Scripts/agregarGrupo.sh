#!/bin/bash

echo "===================="
echo "    AGREGAR GRUPO  "
echo "===================="

read -p "Ingrese el nombre del grupo a agregar: " grupoUsuario
grupo=$(echo "$grupoUsuario" | tr '[:upper:]' '[:lower:]')  # Convertir a minúsculas

if [ $(getent group "$grupo") ]; then
    echo "El grupo '$grupo' ya existe."
else
    sudo groupadd "$grupo"
    if [ $? -eq 0 ]; then
        echo "Grupo '$grupo' agregado exitosamente."
    else
        echo "Error al agregar el grupo '$grupo'."
    fi
fi