#!/bin/bash

echo "===== Configurar Rotación de Logs ====="

cat <<'EOF' >/etc/logrotate.d/sistema-personalizado
/var/log/syslog {
    rotate 7
    daily
    missingok
    notifempty
    compress
    delaycompress
    create 0640 root adm
}
EOF

echo "Configuración de rotación aplicada en /etc/logrotate.d/sistema-personalizado"
