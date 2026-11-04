#!/bin/bash

echo "======== Permitir HTTP/HTTPS ========"

firewall-cmd --permanent --add-service=http
firewall-cmd --reload

echo "HTTP/HTTPS ha sido permitido en el firewall."