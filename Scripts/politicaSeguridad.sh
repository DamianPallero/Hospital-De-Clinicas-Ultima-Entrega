#!/bin/bash

echo "======== Establecer Políticas de Seguridad ========"

echo "Estableciendo políticas de seguridad en el firewall..."
echo "Cambiando la zona por defecto a 'drop'..."
firewall-cmd --set-default-zone=drop