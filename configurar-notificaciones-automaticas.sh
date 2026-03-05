#!/bin/bash
# Script para configurar notificaciones automáticas en el VPS
# Ejecutar este script en el servidor VPS

echo "=== Configurando Notificaciones Automáticas ==="

# Generar token seguro para cron
CRON_TOKEN=$(openssl rand -hex 32)
echo "Token generado: $CRON_TOKEN"

# Agregar CRON_TOKEN al archivo .env del servidor
cd /var/www/sisee
if ! grep -q "CRON_TOKEN" .env 2>/dev/null; then
    echo "CRON_TOKEN=$CRON_TOKEN" >> .env
    echo "✓ Token agregado al archivo .env"
else
    sed -i "s/^CRON_TOKEN=.*/CRON_TOKEN=$CRON_TOKEN/" .env
    echo "✓ Token actualizado en .env"
fi

# Mostrar el token (guárdalo en un lugar seguro)
echo ""
echo "=========================================="
echo "IMPORTANTE: Guarda este token de forma segura"
echo "Token CRON: $CRON_TOKEN"
echo "=========================================="
echo ""

# Configurar cron job para notificaciones diarias
# Ejecutar todos los días a las 8:00 AM hora del servidor
CRON_JOB="0 8 * * * /usr/bin/curl -s -X POST 'http://localhost/api/enviar-notificaciones.php?cron_token=$CRON_TOKEN' >> /var/log/notificaciones-cron.log 2>&1"

# Agregar el cron job
(crontab -l 2>/dev/null | grep -v "enviar-notificaciones.php"; echo "$CRON_JOB") | crontab -

echo "✓ Cron job configurado para ejecutarse diariamente a las 8:00 AM"

# Verificar que se agregó correctamente
echo ""
echo "=== Cron jobs actuales ==="
crontab -l | grep notificaciones

echo ""
echo "=== Verificando configuración de email ==="
php /var/www/sisee/api/cron/verificar-email-config.php

echo ""
echo "=== Configuración completada ==="
echo ""
echo "Para probar el envío de notificaciones manualmente:"
echo "curl -X POST 'http://localhost/api/enviar-notificaciones.php?cron_token=$CRON_TOKEN'"
echo ""
echo "Para ver logs del cron:"
echo "tail -f /var/log/notificaciones-cron.log"
