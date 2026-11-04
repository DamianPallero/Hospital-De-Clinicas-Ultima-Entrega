#!/bin/bash

echo "===== Interfaces de Red ====="

if command -v ip >/dev/null 2>&1; then
    ip -br addr show
else
    ifconfig -a
fi
