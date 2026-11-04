#!/bin/bash

echo "======== Bloquear Direcciones MAC ========"

read -p "Ingrese la dirección MAC que desea bloquear: " mac
mac=$(echo "$mac" | xargs)  # Eliminar espacios en blanco al inicio y al final
if [ -n "$mac" ]; then
    firewall-cmd --permanent --add-rich-rule="rule family='ethernet' source mac='$mac' reject"
else
    echo "No se ingresó ninguna dirección MAC. Operación cancelada."
fi
firewall-cmd --reload
echo "La dirección MAC $mac ha sido bloqueada en el firewall."