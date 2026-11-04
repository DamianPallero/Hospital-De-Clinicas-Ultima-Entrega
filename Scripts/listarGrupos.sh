#!/bin/bash

echo "===================="
echo "    LISTAR GRUPOS  "
echo "===================="

getent group | awk -F: '$3 >= 1000 {print $1}' | sort