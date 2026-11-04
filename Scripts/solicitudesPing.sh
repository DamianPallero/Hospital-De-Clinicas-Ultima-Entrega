#!/bin/bash

echo "======== Habilitar Solicitudes Ping ========"

firewall-cmd --permanent --add-rich-rule="rule protocol value='icmp' accept"
firewall-cmd --reload
echo "Las solicitudes ping han sido habilitadas en el firewall."