# Sistema de Notificaciones por Email - SISEE

## 📋 Descripción General

Sistema completo de notificaciones por correo electrónico para gestores de departamentos. Permite enviar alertas automáticas sobre peticiones pendientes según configuraciones personalizadas por usuario.

## ✨ Características

- ✅ Notificaciones automáticas programadas (diarias/semanales)
- ✅ Configuración personalizada por usuario
- ✅ Filtrado por municipio opcional
- ✅ Umbrales configurables de peticiones
- ✅ Historial completo de notificaciones enviadas
- ✅ Correos HTML responsive y profesionales
- ✅ Soporte para múltiples proveedores SMTP (Gmail, Outlook, etc.)

---

## 🚀 Instalación y Configuración

### 1. Requisitos Previos

- PHP 7.4 o superior
- MySQL/MariaDB
- Composer (para PHPMailer)
- Servidor SMTP configurado (Gmail, Outlook, servidor propio, etc.)

### 2. Instalación de Dependencias

Si aún no está instalado PHPMailer:

```bash
cd c:\xampp\htdocs\SISEE
composer install
```

### 3. Ejecutar Migraciones de Base de Datos

Las migraciones ya fueron ejecutadas, pero si necesitas replicarlas en otro ambiente:

```sql
-- Ver archivo: database/notificaciones-migration.sql
-- Agrega columnas Email a Usuario
-- Crea tablas NotificacionConfiguracion y NotificacionHistorial
-- Crea vista vista_peticiones_pendientes_departamento
```

### 4. Configuración de Variables de Entorno

#### Crear archivo .env

```bash
cd c:\xampp\htdocs\SISEE
copy .env.example .env
```

#### Editar .env con tus credenciales

```env
# GMAIL (Recomendado para desarrollo)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=tu-email@gmail.com
SMTP_PASSWORD=xxxx-xxxx-xxxx-xxxx  # App Password de 16 caracteres
SMTP_FROM_EMAIL=tu-email@gmail.com
SMTP_FROM_NAME=Sistema de Gestión SISEE

# Token de seguridad para cron jobs
CRON_TOKEN=tu-token-aleatorio-muy-seguro-de-32-caracteres-minimo

# URL de la aplicación
APP_URL=https://tu-dominio.com
```

### 5. Configuración de Gmail (Método Recomendado)

#### Paso 1: Activar verificación en 2 pasos
1. Ve a tu cuenta de Google: https://myaccount.google.com
2. **Seguridad** → **Verificación en dos pasos**
3. Activar si no está activado

#### Paso 2: Generar App Password
1. Ve a: https://myaccount.google.com/apppasswords
2. **Nombre de la aplicación**: "SISEE Notificaciones"
3. Copiar la contraseña de 16 caracteres generada
4. Pegar en `.env` como `SMTP_PASSWORD`

#### Configuración final en .env:
```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=tu-email@gmail.com
SMTP_PASSWORD=xxxx xxxx xxxx xxxx  # Los espacios son opcionales
SMTP_FROM_EMAIL=tu-email@gmail.com
SMTP_FROM_NAME=Sistema SISEE
```

### 6. Configuración de Otros Proveedores

#### Outlook/Hotmail
```env
SMTP_HOST=smtp-mail.outlook.com
SMTP_PORT=587
SMTP_USERNAME=tu-email@outlook.com
SMTP_PASSWORD=tu-contraseña
```

#### Yahoo
```env
SMTP_HOST=smtp.mail.yahoo.com
SMTP_PORT=587
SMTP_USERNAME=tu-email@yahoo.com
SMTP_PASSWORD=tu-app-password
```

#### Office 365
```env
SMTP_HOST=smtp.office365.com
SMTP_PORT=587
SMTP_USERNAME=tu-email@tuempresa.com
SMTP_PASSWORD=tu-contraseña
```

---

## 🔧 Configuración en el Frontend

### 1. Importar el Componente

En tu router o componente principal:

```javascript
import ConfiguracionNotificaciones from '@/components/ConfiguracionNotificaciones.vue';
```

### 2. Agregar Ruta (Opcional)

```javascript
// router/index.js
{
  path: '/notificaciones',
  name: 'Notificaciones',
  component: ConfiguracionNotificaciones,
  meta: { requiresAuth: true }
}
```

### 3. Agregar en el Menú

```vue
<router-link to="/notificaciones">
  <i class="fas fa-bell"></i>
  Notificaciones
</router-link>
```

---

## 📧 Uso del Sistema

### Para Usuarios (Gestores de Departamento)

#### 1. Configurar Email

1. Acceder a **Configuración de Notificaciones**
2. Ingresar tu email personal
3. Clic en **"Actualizar Email"**
4. **Enviar correo de prueba** para verificar

#### 2. Activar Notificaciones

1. Activar el toggle **"Activar notificaciones por email"**
2. Configurar opciones según preferencia

#### 3. Opciones de Configuración

| Opción | Descripción |
|--------|-------------|
| **Filtrar por municipio** | Solo peticiones de tu municipio vs todas del departamento |
| **Frecuencia** | Diaria, Semanal o Inmediata |
| **Hora de envío** | Hora aproximada para recibir notificaciones |
| **Umbral mínimo** | Cantidad mínima de peticiones para enviar notificación |
| **Tipos** | Notificar nuevas asignaciones y/o peticiones vencidas |

---

## ⚙️ Automatización (Cron Jobs)

### Configuración en Servidor Linux/Unix

#### Editar crontab

```bash
crontab -e
```

#### Agregar línea para ejecución diaria a las 8:00 AM

```cron
# Notificaciones diarias a las 8:00 AM
0 8 * * * /usr/bin/php /ruta/completa/a/SISEE/api/cron/notificaciones-diarias.php >> /ruta/a/logs/notificaciones.log 2>&1
```

#### Agregar línea para ejecución semanal (Lunes 8:00 AM)

```cron
# Notificaciones semanales los Lunes a las 8:00 AM
0 8 * * 1 /usr/bin/php /ruta/completa/a/SISEE/api/cron/notificaciones-diarias.php >> /ruta/a/logs/notificaciones.log 2>&1
```

### Configuración en Windows (XAMPP)

#### Método 1: Programador de Tareas de Windows

1. Abrir **Programador de tareas** (`taskschd.msc`)
2. **Crear tarea básica**
3. **Nombre**: "SISEE - Notificaciones Diarias"
4. **Desencadenador**: Diariamente a las 8:00 AM
5. **Acción**: Iniciar un programa
   - **Programa**: `C:\xampp\php\php.exe`
   - **Argumentos**: `C:\xampp\htdocs\SISEE\api\cron\notificaciones-diarias.php`
6. Finalizar y probar

#### Método 2: Script Batch

Crear archivo `ejecutar-notificaciones.bat`:

```batch
@echo off
cd C:\xampp\htdocs\SISEE
C:\xampp\php\php.exe api\cron\notificaciones-diarias.php
```

Programar con Programador de Tareas para ejecutar este BAT diariamente.

### Método 3: Llamada HTTP con Cron Token

Si tienes acceso a un servicio de cron online (como cron-job.org):

```bash
curl -X POST "https://tu-dominio.com/api/enviar-notificaciones.php?cron_token=TU_TOKEN_SEGURO"
```

---

## 🧪 Pruebas

### 1. Prueba Manual de Envío

```bash
# Desde línea de comandos
cd c:\xampp\htdocs\SISEE
php api\cron\notificaciones-diarias.php
```

Salida esperada:
```
[2026-02-25 08:00:00] Iniciando proceso de notificaciones diarias
Usuarios a notificar: 2
Procesando usuario: juan.perez (juan@example.com)...
  Peticiones pendientes: 15, Umbral: 5
  [ENVIADO] Notificación enviada correctamente
[2026-02-25 08:00:05] Proceso finalizado
```

### 2. Prueba desde Frontend

1. Acceder como usuario con rol 9 (Gestor)
2. Ir a **Configuración de Notificaciones**
3. Configurar email
4. Clic en **"Enviar correo de prueba"**
5. Verificar recepción en bandeja de entrada

### 3. Verificar Logs

```bash
# Ver logs de email
tail -f c:\xampp\htdocs\SISEE\api\logs\email.log

# Ver logs de notificaciones programadas
tail -f c:\xampp\htdocs\SISEE\api\logs\notificaciones.log
```

---

## 📊 Endpoints API

### GET /api/notificaciones.php
Obtener configuración de notificaciones del usuario actual

**Response:**
```json
{
  "success": true,
  "data": {
    "Id": 1,
    "IdUsuario": 5,
    "Email": "usuario@example.com",
    "NotificacionesActivas": 1,
    "FiltrarPorMunicipio": 0,
    "FrecuenciaNotificacion": "diaria",
    "HoraEnvio": "08:00:00",
    "UmbralPeticionesPendientes": 5,
    "nombre_unidad": "Departamento de Obras Públicas",
    "peticiones_pendientes_actuales": 12
  }
}
```

### PUT /api/notificaciones.php
Actualizar configuración de notificaciones

**Request Body:**
```json
{
  "NotificacionesActivas": 1,
  "FiltrarPorMunicipio": 0,
  "FrecuenciaNotificacion": "diaria",
  "HoraEnvio": "08:00:00",
  "UmbralPeticionesPendientes": 5
}
```

### POST /api/notificaciones.php
Actualizar email del usuario

**Request Body:**
```json
{
  "email": "nuevo-email@example.com"
}
```

### POST /api/enviar-notificaciones.php
Enviar notificaciones (manual o automático)

**Prueba de correo:**
```json
{
  "testEmail": true
}
```

**Envío manual a usuario específico:**
```json
{
  "userId": 5
}
```

### GET /api/enviar-notificaciones.php
Obtener historial de notificaciones

**Query Params:**
- `limit`: Número de registros (default: 50)

---

## 🔍 Troubleshooting

### Error: "Configuración incompleta"

**Causa**: Variables de entorno no configuradas

**Solución**:
1. Verificar que existe archivo `.env` en la raíz
2. Verificar que contiene todas las variables SMTP
3. Reiniciar servidor web después de modificar `.env`

### Error: "SMTP Error: Could not authenticate"

**Causa**: Credenciales incorrectas

**Solución Gmail**:
1. Verificar que usas App Password (no contraseña normal)
2. Verificar verificación en 2 pasos activada
3. Generar nuevo App Password si es necesario

**Solución general**:
1. Verificar usuario y contraseña
2. Verificar que el servidor SMTP permite conexiones desde tu IP

### Error: "Could not connect to SMTP host"

**Causa**: Problema de red o firewall

**Solución**:
1. Verificar host y puerto correctos
2. Verificar firewall no bloquea puerto 587
3. Probar con puerto alternativo (465 para SSL)

### Los correos llegan a SPAM

**Solución**:
1. Configurar SPF, DKIM y DMARC en tu dominio
2. Usar email corporativo en lugar de Gmail personal
3. Aumentar reputación enviando emails regularmente
4. Verificar que el contenido no tiene palabras spam

### No se ejecuta el cron job

**Solución Linux**:
```bash
# Verificar cron está corriendo
systemctl status cron

# Ver logs de cron
tail -f /var/log/syslog | grep CRON

# Verificar permisos del script
chmod +x /ruta/a/SISEE/api/cron/notificaciones-diarias.php
```

**Solución Windows**:
1. Verificar tarea está habilitada en Programador de Tareas
2. Verificar usuario tiene permisos
3. Probar ejecución manual desde línea de comandos

---

## 📈 Monitoreo

### Consultas SQL Útiles

#### Ver usuarios con notificaciones activas
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

#### Ver historial de envíos recientes
```sql
SELECT 
    nh.*,
    u.Usuario,
    u.Email
FROM NotificacionHistorial nh
INNER JOIN Usuario u ON nh.IdUsuario = u.Id
WHERE nh.FechaEnvio >= DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY nh.FechaEnvio DESC;
```

#### Ver tasa de éxito de notificaciones
```sql
SELECT 
    Estado,
    COUNT(*) as Total,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM NotificacionHistorial), 2) as Porcentaje
FROM NotificacionHistorial
GROUP BY Estado;
```

---

## 🎨 Personalización

### Modificar Template de Email

Editar: `api/services/EmailService.php`

Método: `generarHTMLNotificacionPendientes()`

### Agregar Nuevos Tipos de Notificaciones

1. Agregar nuevo tipo en `NotificacionHistorial.TipoNotificacion`
2. Crear método en `EmailService.php`
3. Agregar lógica en `enviar-notificaciones.php`

### Modificar Frecuencias

Editar valores permitidos en:
- `NotificacionConfiguracion.FrecuenciaNotificacion` (enum)
- Componente Vue: `ConfiguracionNotificaciones.vue`

---

## 📝 Mantenimiento

### Limpieza de Historial Antiguo

```sql
-- Eliminar notificaciones de hace más de 6 meses
DELETE FROM NotificacionHistorial 
WHERE FechaEnvio < DATE_SUB(NOW(), INTERVAL 6 MONTH);
```

### Backup de Configuraciones

```bash
# Exportar configuraciones
mysqldump -u siseg -p sisegestion NotificacionConfiguracion NotificacionHistorial > backup_notificaciones.sql
```

---

## 🤝 Soporte

Para problemas o preguntas:
1. Revisar los logs en `api/logs/`
2. Verificar configuración en `.env`
3. Consultar sección de Troubleshooting
4. Contactar al administrador del sistema

---

## 📄 Licencia

Sistema desarrollado para SISEE - Todos los derechos reservados.
