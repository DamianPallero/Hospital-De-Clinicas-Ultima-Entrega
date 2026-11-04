#!/bin/bash

echo "===================="
echo "  ELIMINAR RESPALDO "
echo "===================="

echo "¿Desea eliminar un respaldo de logs o de base de datos?"
echo "1. Respaldo de logs"
echo "2. Respaldo de base de datos"
read -p "Ingrese el número correspondiente a su elección: " opcion

if [ "$opcion" == "1" ]; then
    echo "Lista de respaldos de logs disponibles:"
    ls -l /root/respaldos_logs/*.tar.gz
    read -p "Ingrese el nombre del archivo de respaldo de logs a eliminar (con extensión .tar.gz): " respaldo
    rm /root/respaldos_logs/$respaldo
    echo "Respaldo de logs eliminado exitosamente."
elif [ "$opcion" == "2" ]; then
    echo "Lista de respaldos de base de datos disponibles:"
    ls -l /root/respaldos_bd/*.sql
    read -p "Ingrese el nombre del archivo de respaldo de base de datos a eliminar (con extensión .sql): " respaldo
    rm /root/respaldos_bd/$respaldo
    echo "Respaldo de base de datos eliminado exitosamente."
else
    echo "Opción inválida. Por favor, ejecute el script nuevamente y seleccione una opción válida."
fi