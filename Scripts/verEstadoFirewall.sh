#!/bin/bash

echo "======== Ver Estado del Firewall ========"

if [ "$(systemctl is-active firewalld)" = "active" ]; then
    echo "El firewall está activo."
else
    echo "El firewall está inactivo."
fi