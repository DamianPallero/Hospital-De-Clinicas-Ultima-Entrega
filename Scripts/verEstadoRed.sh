#!/bin/bash

echo "====== Ver Estado de la Red ======"

if command -v ip >/dev/null 2>&1; then
    ip addr show
else
    ifconfig
fi

echo ""
echo "Rutas actuales:"
route -n 2>/dev/null || netstat -rn 2>/dev/null || echo "No se pudo mostrar la tabla de rutas."
