#!/bin/bash

echo "===== Ver Logs del Sistema ====="

journalctl -n 50 --no-pager
