# ✅ Sistema de Notificaciones - CONFIRMACIÓN DE FUNCIONAMIENTO

## 🎯 Cómo Funciona (Correctamente)

### Para usuarios con rol **Departamento (RolId = 9)**:

1. **En el Navbar (Sidebar):**
   - Ven un botón **"Notificaciones"** con ícono de campana 🔔
   - El botón aparece junto a "Mis Peticiones"
   - Solo visible si tienen el permiso `gestion_peticiones_departamento`

2. **Al hacer clic en Notificaciones:**
   - Van a `/configuracion/notificaciones`
   - Ven una pantalla para configurar:
     - ✉️ Su email personal
     - ⚙️ Activar/desactivar notificaciones
     - 📅 Frecuencia (diaria, semanal, inmediata)
     - 🕐 Hora de envío
     - 🔢 Umbral mínimo de peticiones
     - 🏙️ Filtrar solo por su municipio
     - 📧 Botón para enviar correo de prueba

3. **Sistema guarda configuración:**
   - Cada usuario tiene su propia configuración en `NotificacionConfiguracion`
   - `IdUsuario` = ID del usuario
   - `IdUnidad` = Departamento al que pertenece
   - `Email` = Donde recibe las notificaciones

---

## ✅ Múltiples Usuarios por Departamento - SÍ FUNCIONA

### Ejemplo Real:

**Departamento de Obras Públicas (IdUnidad = 5)**

| Usuario | Email | Notificaciones | Filtro Municipio |
|---------|-------|----------------|------------------|
| Juan Pérez | juan@gmail.com | ✅ Activas (Diaria 8:00 AM) | ❌ No (todas del depto) |
| María López | maria@outlook.com | ✅ Activas (Semanal Lunes) | ✅ Sí (solo su municipio) |
| Carlos Ruiz | carlos@yahoo.com | ❌ Inactivas | - |

**Resultado:**
- Juan recibe email diario a las 8 AM con TODAS las peticiones del departamento
- María recibe email los lunes SOLO con peticiones de su municipio
- Carlos NO recibe emails (desactivó notificaciones)

**Cada usuario configura independientemente su email y preferencias.**

---

## 🔑 Permisos Necesarios

### Rol Departamento (RolId = 9) necesita:

```sql
-- Verificar que tiene el permiso:
SELECT p.Codigo, p.Nombre 
FROM RolPermiso rp
INNER JOIN Permiso p ON rp.IdPermiso = p.Id
WHERE rp.IdRolSistema = 9 AND p.Codigo = 'gestion_peticiones_departamento';
```

Si **NO** tiene el permiso, ejecuta:

```sql
-- Obtener ID del permiso
SELECT Id FROM Permiso WHERE Codigo = 'gestion_peticiones_departamento';

-- Asignar permiso al rol 9 (suponiendo que el permiso tiene Id = X)
INSERT INTO RolPermiso (IdRolSistema, IdPermiso) 
VALUES (9, X);  -- Reemplaza X con el ID del permiso
```

---

## 📧 Configuración del .env (Para el Servidor)

El `.env` se configura **UNA VEZ** a nivel sistema (no por usuario):

```env
# Gmail del SISTEMA (quien envía los correos)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=sistema.sisee@gmail.com     # Email del sistema
SMTP_PASSWORD=xxxx xxxx xxxx xxxx         # App Password del sistema
SMTP_FROM_EMAIL=sistema.sisee@gmail.com  # Remitente
SMTP_FROM_NAME=Sistema SISEE Notificaciones

CRON_TOKEN=token-seguro-aleatorio
APP_URL=http://localhost/SISEE
```

### Cómo funciona:

1. **El sistema envía DESDE:** `sistema.sisee@gmail.com`
2. **Cada usuario recibe EN:** Su email personal configurado
3. **Ejemplo de flujo:**
   ```
   [Sistema SISEE] --envía--> [Juan Pérez]
   Desde: sistema.sisee@gmail.com
   Para: juan@gmail.com
   Asunto: Tienes 12 peticiones pendientes en Obras Públicas
   ```

---

## 🧪 Cómo Probar

### 1. Como Usuario de Departamento:

```bash
# Iniciar sesión con un usuario que tenga rol Departamento (RolId=9)
# Por ejemplo: usuario "departamento_obras"
```

### 2. Verificar que ves el botón:

- Abre el navbar (botón flotante azul abajo a la derecha)
- Deberías ver: **"Notificaciones"** con ícono 🔔
- Entre "Mis Peticiones" y otros botones

### 3. Configurar tu email:

1. Clic en **"Notificaciones"**
2. Ingresa tu email personal: `tu-email@gmail.com`
3. Clic en **"Actualizar Email"**
4. Activa el toggle **"Activar notificaciones por email"**
5. Configura frecuencia, hora, umbral
6. Clic en **"Enviar Correo de Prueba"**
7. Revisa tu bandeja (y SPAM)

---

## 📊 Base de Datos

### Tabla NotificacionConfiguracion

```sql
-- Ver todas las configuraciones actuales
SELECT 
    u.Usuario,
    u.Email as EmailEnUsuario,
    nc.NotificacionesActivas,
    nc.FrecuenciaNotificacion,
    nc.HoraEnvio,
    nc.FiltrarPorMunicipio,
    uni.nombre_unidad as Departamento
FROM NotificacionConfiguracion nc
INNER JOIN Usuario u ON nc.IdUsuario = u.Id
LEFT JOIN unidades uni ON nc.IdUnidad = uni.id;
```

### Ejemplo de datos:

| Usuario | Email | Activas | Frecuencia | Hora | Departamento |
|---------|-------|---------|------------|------|--------------|
| juan.perez | juan@gmail.com | 1 | diaria | 08:00:00 | Obras Públicas |
| maria.lopez | maria@outlook.com | 1 | semanal | 09:00:00 | Obras Públicas |
| carlos.ruiz | carlos@yahoo.com | 0 | diaria | 08:00:00 | Seguridad |

---

## ⚙️ Cron Job (Envío Automático)

### Windows:

```powershell
# Programador de Tareas
# Ejecutar diariamente a las 8:00 AM
C:\xampp\php\php.exe C:\xampp\htdocs\SISEE\api\cron\notificaciones-diarias.php
```

### Linux:

```bash
# Agregar a crontab
crontab -e

# Ejecutar todos los días a las 8:00 AM
0 8 * * * /usr/bin/php /var/www/html/SISEE/api/cron/notificaciones-diarias.php
```

El script automáticamente:
1. Busca usuarios con `NotificacionesActivas = 1`
2. Cuenta sus peticiones pendientes
3. Si superan el umbral, envía email
4. Respeta frecuencia, horario y filtros

---

## ✅ CONFIRMACIÓN FINAL

**SÍ, así funciona correctamente:**

✅ Múltiples usuarios pueden tener rol Departamento  
✅ Cada usuario configura su propio email  
✅ Cada usuario recibe notificaciones individuales  
✅ Un departamento puede tener 1, 5, 10 o más usuarios  
✅ Cada uno recibe emails según su configuración  
✅ El sistema SMTP se configura UNA vez en .env  
✅ Los usuarios NO ven el .env, solo configuran su email desde el frontend  

---

## 🚀 Siguiente Paso

1. **Refresca el navegador** (Ctrl+F5)
2. **Inicia sesión** con un usuario de rol Departamento
3. **Abre el navbar** (botón flotante azul)
4. **Busca el botón "Notificaciones"** 🔔
5. **Configura tu email**
6. **Envía correo de prueba**
7. **¡Listo!**

---

## 📞 Troubleshooting

### No veo el botón "Notificaciones"

Verifica:
```sql
-- ¿Tu usuario tiene rol Departamento?
SELECT r.Nombre 
FROM UsuarioRol ur
INNER JOIN RolSistema r ON ur.IdRolSistema = r.Id
WHERE ur.IdUsuario = TU_USER_ID;

-- ¿El rol tiene el permiso correcto?
SELECT p.Codigo
FROM RolPermiso rp
INNER JOIN Permiso p ON rp.IdPermiso = p.Id
WHERE rp.IdRolSistema = 9 AND p.Codigo = 'gestion_peticiones_departamento';
```

### Error al guardar configuración

Verifica:
```sql
-- ¿Tienes IdUnidad asignado?
SELECT IdUnidad FROM Usuario WHERE Id = TU_USER_ID;
```

Si es NULL, asigna una unidad:
```sql
UPDATE Usuario SET IdUnidad = ID_DEPARTAMENTO WHERE Id = TU_USER_ID;
```

---

¡Todo corregido! 🎉
