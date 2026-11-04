#!/bin/bash

echo "===== Reiniciar Servicio de Red ======"

if systemctl list-unit-files | grep -q '^NetworkManager.service'; then
    sudo systemctl restart NetworkManager
    echo "Servicio NetworkManager reiniciado."
elif systemctl list-unit-files | grep -q '^networking.service'; then
    sudo systemctl restart networking
    echo "Servicio networking reiniciado."
else
    echo "No se encontró un servicio de red compatible en este sistema."
fi
