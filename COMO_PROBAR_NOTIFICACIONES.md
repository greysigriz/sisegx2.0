# 🚀 Cómo Probar el Sistema de Notificaciones - GUÍA RÁPIDA

## Tranquilo, te explico paso a paso 😊

---

## 📧 OPCIÓN 1: Usar tu Gmail Personal (RECOMENDADO para pruebas)

### Paso 1: Configurar el archivo .env

1. **Abre el archivo** `.env` (está en `c:\xampp\htdocs\SISEE\.env`)

2. **Reemplaza estos valores:**

```env
# Pon tu email de Gmail aquí
SMTP_USERNAME=tu-email@gmail.com
SMTP_FROM_EMAIL=tu-email@gmail.com

# La contraseña la generamos en el siguiente paso
SMTP_PASSWORD=aqui-va-la-contraseña

# El nombre que aparece como remitente
SMTP_FROM_NAME=Sistema SISEE Notificaciones
```

### Paso 2: Generar App Password de Gmail

**¿Por qué necesito esto?** Gmail no permite usar tu contraseña normal por seguridad. Necesitas generar una "contraseña de aplicación".

**Pasos:**

1. **Ve a tu cuenta de Google:** https://myaccount.google.com

2. **Seguridad (menú izquierdo)** → Busca **"Verificación en 2 pasos"**
   - Si NO está activada → Actívala primero
   - Si ya está activada → Continúa al paso 3

3. **Contraseñas de aplicaciones:** https://myaccount.google.com/apppasswords
   - Si el link no funciona: **Seguridad** → **Verificación en 2 pasos** → Al final de la página: **Contraseñas de aplicaciones**

4. **Crear contraseña:**
   - **Nombre:** "SISEE Notificaciones" (o lo que quieras)
   - Clic en **"Crear"**

5. **Copiar la contraseña** (son 16 caracteres, ejemplo: `abcd efgh ijkl mnop`)

6. **Pegar en tu .env:**
   ```env
   SMTP_PASSWORD=abcd efgh ijkl mnop
   ```
   (Los espacios son opcionales, puedes ponerla con o sin espacios)

### Paso 3: Generar Token de Seguridad

El `CRON_TOKEN` es para que solo tu servidor pueda ejecutar envíos automáticos.

**En PowerShell:**

```powershell
-join ((65..90) + (97..122) + (48..57) | Get-Random -Count 32 | ForEach-Object {[char]$_})
```

**Copia el resultado y pégalo en `.env`:**

```env
CRON_TOKEN=x8k2Jf9mNp3Qz5Rt7Vw1Yx4Ca6Eb8Gd
```

### Paso 4: Ejemplo Final del .env

```env
VITE_API_URL=http://localhost/SISEE/api
VITE_BACKEND_URL=http://127.0.0.1:8000

# Configuración SMTP
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=miusuario@gmail.com
SMTP_PASSWORD=abcd efgh ijkl mnop
SMTP_FROM_EMAIL=miusuario@gmail.com
SMTP_FROM_NAME=Sistema SISEE Notificaciones
CRON_TOKEN=x8k2Jf9mNp3Qz5Rt7Vw1Yx4Ca6Eb8Gd
APP_URL=http://localhost/SISEE
```

---

## 🧪 PROBAR QUE FUNCIONA

### Método 1: Desde la Terminal (Más Rápido)

```powershell
cd C:\xampp\htdocs\SISEE
C:\xampp\php\php.exe api/test-notificaciones.php
```

**✅ Si funciona, verás:**
```
=== PRUEBA DE SISTEMA DE NOTIFICACIONES ===
✅ Configuración SMTP detectada correctamente
✅ Email de prueba enviado correctamente a: tu-email@gmail.com
```

**❌ Si falla:**
- Revisa que copiaste bien el App Password
- Verifica que activaste la verificación en 2 pasos en Google
- Confirma que reiniciaste Apache después de modificar .env

### Método 2: Desde el Frontend

1. **Inicia sesión** en el sistema SISEE

2. **Ve a:** Configuración → Notificaciones por Email

3. **Configura tu email:**
   - Ingresa tu email personal (puede ser el mismo de Gmail u otro)
   - Clic en **"Actualizar Email"**

4. **Envía una prueba:**
   - Clic en **"Enviar Correo de Prueba"**
   - Revisa tu bandeja (y SPAM)

5. **Activa las notificaciones:**
   - Activa el switch **"Activar notificaciones por email"**
   - Configura frecuencia: Diaria / Semanal
   - Umbral mínimo: 5 peticiones (o lo que quieras)

---

## 🔍 SOLUCIÓN DE PROBLEMAS

### Error: "Could not authenticate"

**Causa:** App Password incorrecta

**Solución:**
1. Genera una nueva App Password en Google
2. Cópiala exactamente (sin cambiar nada)
3. Pégala en `.env` → `SMTP_PASSWORD`
4. **Reinicia Apache**

### Error: "Connection timed out"

**Causa:** Firewall bloqueando puerto 587

**Solución:**
1. Verifica tu antivirus/firewall
2. Intenta con otro puerto en `.env`:
   ```env
   SMTP_PORT=465
   ```
3. Reinicia Apache

### No recibo el correo

**Revisa:**
1. **Carpeta de SPAM** ← importante!
2. Los logs: `api/logs/email.log`
3. Que pusiste el email correcto

### "Configuración incompleta"

**Solución:**
1. Verifica que `.env` existe en la raíz (`C:\xampp\htdocs\SISEE\.env`)
2. Verifica que tiene TODAS las variables SMTP
3. **Reinicia Apache** (Panel de XAMPP → Apache → Stop → Start)

---

## 📧 OPCIÓN 2: Crear un Gmail Nuevo (Si quieres un email específico para el sistema)

Si prefieres no usar tu Gmail personal:

1. **Crea una cuenta nueva de Gmail:**
   - Ejemplo: `sisee.notificaciones@gmail.com`

2. **Activa verificación en 2 pasos** en esa cuenta

3. **Genera App Password** para esa cuenta

4. **Configura el .env** con ese email:
   ```env
   SMTP_USERNAME=sisee.notificaciones@gmail.com
   SMTP_PASSWORD=xxxx xxxx xxxx xxxx
   SMTP_FROM_EMAIL=sisee.notificaciones@gmail.com
   SMTP_FROM_NAME=Sistema SISEE
   ```

5. **Los correos de prueba los enviarás a tu email personal** (configúralo en el frontend)

---

## 📧 OPCIÓN 3: Usar Outlook/Hotmail (Alternativa)

Si prefieres Outlook:

```env
SMTP_HOST=smtp-mail.outlook.com
SMTP_PORT=587
SMTP_USERNAME=tu-email@outlook.com
SMTP_PASSWORD=tu-contraseña-normal
SMTP_FROM_EMAIL=tu-email@outlook.com
SMTP_FROM_NAME=Sistema SISEE
```

**NO necesitas App Password en Outlook**, usa tu contraseña normal.

---

## ✅ CHECKLIST COMPLETO

- [ ] Archivo `.env` existe en `C:\xampp\htdocs\SISEE\.env`
- [ ] Variable `SMTP_USERNAME` con tu Gmail
- [ ] Variable `SMTP_PASSWORD` con App Password de 16 caracteres
- [ ] Variable `SMTP_FROM_EMAIL` con tu Gmail
- [ ] Variable `CRON_TOKEN` con token aleatorio generado
- [ ] Verificación en 2 pasos activada en Google
- [ ] App Password generada en Google
- [ ] Apache reiniciado después de modificar `.env`
- [ ] Ejecutado `test-notificaciones.php` sin errores
- [ ] Correo de prueba recibido (revisar SPAM)
- [ ] Configuración guardada en el frontend

---

## 🎯 RESUMEN RÁPIDO

```powershell
# 1. Editar .env con tu Gmail y App Password
notepad C:\xampp\htdocs\SISEE\.env

# 2. Reiniciar Apache
# (Hazlo desde el panel de XAMPP)

# 3. Probar
cd C:\xampp\htdocs\SISEE
C:\xampp\php\php.exe api/test-notificaciones.php

# 4. Si funciona, ya está listo! Configura el frontend
```

---

## 📞 ¿Sigues con problemas?

**Revisa los logs:**
```powershell
# Ver últimas líneas del log
Get-Content C:\xampp\htdocs\SISEE\api\logs\email.log -Tail 20
```

**Verifica variables cargadas:**
```powershell
C:\xampp\php\php.exe -r "require 'config/env.php'; var_dump(getenv('SMTP_HOST'));"
```

---

## 🎉 ¡YA ESTÁ!

Una vez configurado, el sistema:
- ✅ Enviará notificaciones automáticas según la configuración de cada usuario
- ✅ Respetará horarios y frecuencias configuradas
- ✅ Solo notificará cuando haya peticiones pendientes
- ✅ Guardará historial de todos los envíos

**¿Siguiente paso?** Configura el cron job para envíos automáticos (ver `Sistema_Notificaciones_Email.md`)
