#!/bin/bash

echo "===== Diagnosticar Conectividad ====="

read -p "Ingrese la dirección o hostname a probar (ej. 8.8.8.8): " destino

if [ -z "$destino" ]; then
    destino="8.8.8.8"
fi

ping -c 4 "$destino"
