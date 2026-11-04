#!/bin/bash

echo "===== Vaciar Logs ====="

sudo journalctl --vacuum-time=1days

echo "Los logs fueron compactados eliminando registros antiguos."
