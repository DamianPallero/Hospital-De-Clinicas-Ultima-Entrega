#!/bin/bash

echo "===== Listar Bases de Datos ====="

mysql -uroot -p -e "SHOW DATABASES;"
