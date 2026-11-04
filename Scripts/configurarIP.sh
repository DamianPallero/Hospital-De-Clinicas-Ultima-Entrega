#!/bin/bash

echo "======= Configurar IP ======="

read -p "Ingrese la interfaz de red (ej. eth0): " interfaz
read -p "Ingrese la dirección IP (ej. 192.168.1.10/24): " ip

if [ -z "$interfaz" ] || [ -z "$ip" ]; then
    echo "Debe ingresar la interfaz y la IP."
    exit 1
fi

if command -v ip >/dev/null 2>&1; then
    sudo ip addr add "$ip" dev "$interfaz"
    echo "La dirección IP $ip se ha configurado en la interfaz $interfaz."
else
    echo "El comando ip no está disponible. Configure la IP manualmente."
fi
