#!/bin/bash

echo "===== Buscar en Logs ====="

read -p "Ingrese el texto a buscar en los logs: " texto

if [ -z "$texto" ]; then
    echo "Debe ingresar un texto para buscar."
    exit 1
fi

journalctl --no-pager | grep -i "$texto" || echo "No se encontraron coincidencias."
