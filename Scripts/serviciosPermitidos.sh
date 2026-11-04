#!/bin/bash
echo "======== Servicios Permitidos ========"
zona=$(firewall-cmd --get-default-zone)
echo "Zona por defecto: $zona"
echo "Servicios habilitados en la zona $zona:"
firewall-cmd --zone=$zona --list-services