#!/bin/bash

echo "===== Estado de Docker ====="

if command -v docker >/dev/null 2>&1; then
    docker info
else
    echo "Docker no está instalado o no está en el PATH."
fi
