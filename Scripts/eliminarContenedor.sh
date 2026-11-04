#!/bin/bash

echo "===== Eliminar Contenedor ====="

read -p "Ingrese el nombre o ID del contenedor: " contenedor

if [ -z "$contenedor" ]; then
    echo "Debe ingresar un nombre o ID de contenedor."
    exit 1
fi

docker rm -f "$contenedor"

echo "Contenedor '$contenedor' eliminado."
