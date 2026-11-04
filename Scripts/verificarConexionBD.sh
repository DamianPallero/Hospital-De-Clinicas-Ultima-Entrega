#!/bin/bash

echo "===== Verificar Conexión a Base de Datos ====="

mysqladmin -uroot -p status
