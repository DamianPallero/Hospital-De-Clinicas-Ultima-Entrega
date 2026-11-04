#!/bin/bash

echo "======== Listar Usuarios ========"

echo "Usuarios del sistema:"
getent passwd | awk -F: '$3 >= 1000 && $3 < 65534 {print $1}' | sort
