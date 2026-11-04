#!/bin/bash

echo "======== Bloquear IP ========"

read -p "Ingrese la dirección IP que desea bloquear: " ip
firewall-cmd --permanent --add-rich-rule="rule family='ipv4' source address='$ip' reject"
firewall-cmd --reload

echo "La dirección IP $ip ha sido bloqueada en el firewall."