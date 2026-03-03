# 🚀 Guía Rápida de Inicio - Sistema de Notificaciones por Email

## ⚡ Configuración Rápida (5 minutos)

### Paso 1: Copiar archivo de configuración
```bash
cd C:\xampp\htdocs\SISEE
copy .env.example .env
```

### Paso 2: Configurar credenciales SMTP

Editar `.env` y completar:

#### Para Gmail (Recomendado):
```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=tu-email@gmail.com
SMTP_PASSWORD=xxxx-xxxx-xxxx-xxxx    ← App Password de Gmail
SMTP_FROM_EMAIL=tu-email@gmail.com
SMTP_FROM_NAME=Sistema SISEE

CRON_TOKEN=genera-un-token-aleatorio-seguro-aqui
APP_URL=https://tu-dominio.com
```

**Obtener App Password de Gmail:**
1. Visita: https://myaccount.google.com/apppasswords
2. Crear nueva contraseña de aplicación
3. Copiar los 16 caracteres
4. Pegar en `SMTP_PASSWORD`

### Paso 3: Verificar instalación
```bash
php api\test-notificaciones.php
```

Deberías ver:
```
✅ Todas las variables están configuradas
✅ Conexión a base de datos exitosa
✅ Tablas existen
```

### Paso 4: Enviar correo de prueba

Desde el script o desde el frontend:
- Accede al sistema
- Ve a **Configuración de Notificaciones**
- Ingresa tu email
- Clic en **"Enviar correo de prueba"**

✅ **¡Listo!** El sistema está configurado.

---

## 📋 Configuración para Usuarios

### 1. Configurar Email Personal
1. Login al sistema
2. Ir a **Configuración → Notificaciones**
3. Ingresar tu email
4. **Actualizar Email**

### 2. Activar Notificaciones
- Toggle **"Activar notificaciones por email"**
- Configurar opciones:
  - ☑️ Filtrar por mi municipio (opcional)
  - ⏰ Frecuencia: Diaria/Semanal/Inmediata
  - 🕐 Hora: 08:00 (recomendado)
  - 📊 Umbral: 5 peticiones (mínimo para notificar)

### 3. Verificar
- Clic en **"Enviar correo de prueba"**
- Revisar bandeja de entrada (y spam)

---

## ⚙️ Automatización (Cron Jobs)

### Windows (XAMPP)

1. **Abrir Programador de Tareas** (`Win + R` → `taskschd.msc`)
2. **Crear Tarea Básica**
   - Nombre: `SISEE Notificaciones`
   - Desencadenador: Diariamente a las 8:00 AM
   - Acción: Iniciar programa
     - Programa: `C:\xampp\php\php.exe`
     - Argumentos: `C:\xampp\htdocs\SISEE\api\cron\notificaciones-diarias.php`
3. **Finalizar**

### Linux

```bash
# Editar crontab
crontab -e

# Agregar línea
0 8 * * * /usr/bin/php /var/www/html/SISEE/api/cron/notificaciones-diarias.php
```

---

## 🧪 Pruebas Manuales

### Ejecutar envío manual:
```bash
cd C:\xampp\htdocs\SISEE
php api\cron\notificaciones-diarias.php
```

### Ver logs:
```bash
# Windows
type api\logs\email.log

# Linux
tail -f api/logs/email.log
```

---

## ❗ Solución de Problemas Comunes

### ❌ "SMTP Error: Could not authenticate"
**Solución:**
- Si usas Gmail, verifica que uses **App Password**, NO tu contraseña normal
- Reactiva verificación en 2 pasos
- Genera nuevo App Password

### ❌ "Could not connect to SMTP host"
**Solución:**
- Verifica firewall no bloquea puerto 587
- Prueba puerto 465 (SSL)
- Verifica host correcto

### ❌ Los correos llegan a SPAM
**Solución:**
- Usa email corporativo (no Gmail personal)
- Configura SPF/DKIM en tu dominio
- Aumenta volumen gradualmente

### ❌ No se ejecuta el cron job (Windows)
**Solución:**
- Verifica que la tarea esté habilitada
- Clic derecho → Ejecutar
- Ver historial de la tarea

---

## 📊 Verificar Funcionamiento

### SQL - Ver configuraciones activas:
```sql
SELECT 
    u.Usuario,
    u.Email,
    nc.NotificacionesActivas,
    nc.FrecuenciaNotificacion,
    uni.nombre_unidad
FROM Usuario u
INNER JOIN NotificacionConfiguracion nc ON u.Id = nc.IdUsuario
INNER JOIN unidades uni ON u.IdUnidad = uni.id
WHERE nc.NotificacionesActivas = 1;
```

### SQL - Ver últimas notificaciones:
```sql
SELECT 
    u.Usuario,
    nh.Asunto,
    nh.CantidadPeticionesPendientes,
    nh.Estado,
    nh.FechaEnvio
FROM NotificacionHistorial nh
INNER JOIN Usuario u ON nh.IdUsuario = u.Id
ORDER BY nh.FechaEnvio DESC
LIMIT 10;
```

---

## 🎯 Checklist de Implementación

- [ ] Archivo `.env` configurado con credenciales SMTP
- [ ] Prueba de conexión exitosa (`test-notificaciones.php`)
- [ ] Usuarios con Rol 9 tienen email configurado
- [ ] Correo de prueba recibido correctamente
- [ ] Notificaciones activadas en configuración de usuario
- [ ] Cron job programado y funcionando
- [ ] Logs verificados sin errores

---

## 📚 Documentación Completa

Para detalles completos, ver:
- **`documentation/Sistema_Notificaciones_Email.md`** - Manual completo
- **`.env.example`** - Plantilla de configuración
- **`database/migraciones_notificaciones.sql`** - Scripts SQL

---

## 🆘 Soporte

**Logs importantes:**
- `api/logs/email.log` - Registro de envíos
- `api/logs/database_debug.log` - Conexiones BD

**Scripts de utilidad:**
- `api/test-notificaciones.php` - Verificar configuración
- `api/cron/notificaciones-diarias.php` - Envío programado

---

## ✅ Estados de Peticiones Consideradas "Pendientes"

Las notificaciones incluyen peticiones en estos estados:
- ⏳ **Esperando recepción** - Recién asignadas
- ⚙️ **Aceptado en proceso** - En trabajo activo
- 🔄 **Devuelto a seguimiento** - Requieren nueva acción

**Estados excluidos:**
- ✅ Completado
- ❌ Rechazado

---

**¿Lista la configuración? ¡Empieza a recibir notificaciones automáticas!** 🎉
