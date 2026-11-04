#!/bin/bash

echo "======== Agregar Servicios ========"

read -p "Ingrese el nombre del servicio que desea agregar (por ejemplo, 'ssh', 'http', 'https'): " servicio
servicio=$(echo "$servicio" | xargs)
servicio=$(echo "$servicio" | tr '[:upper:]' '[:lower:]')

if [ -n "$servicio" ]; then
    if firewall-cmd --permanent --add-service=$servicio 2>/dev/null; then
        firewall-cmd --reload
        echo "El servicio '$servicio' ha sido agregado al firewall."
    else
        echo "Error: No se pudo agregar el servicio '$servicio'. Asegúrese de que el nombre del servicio sea válido."
    fi
else
    echo "No se ingresó ningún nombre de servicio. Operación cancelada."
fi