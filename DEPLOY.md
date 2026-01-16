# 🚀 Guía de Despliegue al VPS

Esta guía explica cómo subir cambios al VPS de producción de manera segura y correcta.

---

## 📋 Pre-requisitos

- Git configurado y cambios commiteados
- Acceso SSH al VPS: `root@50.21.181.205`
- Backend Python corriendo en el VPS (puerto 8000)
- Apache configurado en el VPS

---

## � DESPLIEGUE RÁPIDO (TODO EN UNO)

**Ejecuta este comando completo para desplegar todo de una vez:**

```bash
ssh root@50.21.181.205 "cd /var/www/sisee && \
  git stash && \
  git pull && \
  echo 'VITE_API_URL=http://50.21.181.205/api' > .env && \
  echo 'VITE_BACKEND_URL=http://50.21.181.205/api/proxy-clasificacion.php' >> .env && \
  sed -i 's/cookie_domain.*localhost/cookie_domain'\''\'', '\'''\''/' /var/www/sisee/api/login.php && \
  npm install && \
  npm run build && \
  pkill -9 -f uvicorn ; sleep 3 && \
  cd backend && \
  pip3 install -r requirements.txt --break-system-packages 2>/dev/null ; \
  nohup python3 -m uvicorn main:app --host 127.0.0.1 --port 8000 --reload > /var/log/uvicorn.log 2>&1 & \
  sleep 4 && \
  echo '=== VERIFICACIÓN ===' && \
  ps aux | grep uvicorn | grep -v grep && \
  curl -s 'http://localhost/api/proxy-clasificacion.php?path=clasificacion/categorias' | head -100"
```

**Después del despliegue:**
1. Abre http://50.21.181.205 en el navegador
2. Presiona **Ctrl+Shift+R** para recargar sin caché
3. Cierra sesión y vuelve a iniciar sesión (para renovar cookies)
4. Verifica que login, dashboard y clasificador funcionen

---

## �🔄 Proceso de Despliegue

### 1️⃣ **Preparar Cambios Locales**

```bash
# Verificar estado de Git
git status

# Agregar archivos modificados
git add .

# Hacer commit con mensaje descriptivo
git commit -m "Descripción de los cambios"

# Subir a repositorio remoto
git push origin main
```

---

### 2️⃣ **Actualizar Código en el VPS**

```bash
# Conectar al VPS
ssh root@50.21.181.205

# Navegar al directorio del proyecto
cd /var/www/sisee

# Guardar cambios locales antes de pull
git stash

# Hacer pull de los cambios
git pull

# ⚠️ CRÍTICO: Restaurar configuraciones de producción
# El .env DEBE usar proxy PHP, NO localhost
echo 'VITE_API_URL=http://50.21.181.205/api' > .env
echo 'VITE_BACKEND_URL=http://50.21.181.205/api/proxy-clasificacion.php' >> .env

# Verificar que cookie_domain esté vacío (NO 'localhost')
sed -i 's/cookie_domain.*localhost/cookie_domain'\''\'', '\'''\''/' /var/www/sisee/api/login.php

# Confirmar cambios
cat .env
grep "cookie_domain" /var/www/sisee/api/login.php | head -1
```

---

### 3️⃣ **Actualizar FRONTEND (Vue.js)**

```bash
# En el VPS, dentro de /var/www/sisee

# Instalar dependencias si hay cambios en package.json
npm install

# Compilar el frontend para producción
npm run build

# Los archivos compilados van a la carpeta dist/
# Apache los sirve automáticamente
```

**✅ Verificación:**
- Abre http://50.21.181.205 en el navegador
- Presiona Ctrl+Shift+R para recargar sin caché
- Verifica que los cambios se vean correctamente

---

### 4️⃣ **Actualizar BACKEND (Python/FastAPI)**

```bash
# En el VPS, dentro de /var/www/sisee/backend

# Instalar/actualizar dependencias
pip3 install -r requirements.txt --break-system-packages

# Matar proceso anterior (si existe)
pkill -9 -f uvicorn

# Esperar a que el puerto se libere
sleep 3

# Iniciar backend en 127.0.0.1 (solo accesible vía proxy PHP)
cd /var/www/sisee/backend
nohup python3 -m uvicorn main:app --host 127.0.0.1 --port 8000 --reload > /var/log/uvicorn.log 2>&1 &

# Esperar inicio
sleep 4

# Verificar que esté corriendo
ps aux | grep uvicorn | grep -v grep

# Probar el clasificador vía proxy
curl -s 'http://localhost/api/proxy-clasificacion.php?path=clasificacion/categorias' | head -100
```

**✅ Verificación del Backend:**
```bash
# Ver el log del backend (últimas líneas)
tail -20 /var/log/uvicorn.log

# Debe mostrar: "Application startup complete"
```

---

### 5️⃣ **Actualizar Backend PHP (si aplica)**

```bash
# En el VPS
cd /var/www/sisee/api

# No requiere compilación
# Los cambios se aplican automáticamente

# Reiniciar Apache solo si hay problemas
sudo systemctl restart apache2
```

---

## 🔧 Comandos Útiles de Mantenimiento

### Ver logs del Backend Python
```bash
tail -f /var/log/uvicorn.log
```

### Ver logs de Apache
```bash
tail -f /var/log/apache2/error.log
tail -f /var/log/apache2/access.log
```

### Verificar servicios corriendo
```bash
# Ver procesos de Python
ps aux | grep python

# Ver procesos de uvicorn
ps aux | grep uvicorn

# Ver estado de Apache
sudo systemctl status apache2
```

### Reiniciar servicios
```bash
# Reiniciar Apache
sudo systemctl restart apache2

# Reiniciar Backend Python (con verificación)
pkill -9 -f uvicorn
sleep 3
cd /var/www/sisee/backend
nohup python3 -m uvicorn main:app --host 127.0.0.1 --port 8000 --reload > /var/log/uvicorn.log 2>&1 &
sleep 3
ps aux | grep uvicorn | grep -v grep
tail -10 /var/log/uvicorn.log
```

### Probar clasificador
```bash
# Probar desde el servidor (debe devolver JSON con categorías)
curl -s 'http://localhost/api/proxy-clasificacion.php?path=clasificacion/categorias'

# Desde tu computadora (debe funcionar igual)
curl -s 'http://50.21.181.205/api/proxy-clasificacion.php?path=clasificacion/categorias'
```

---

## ⚠️ Problemas Comunes y Soluciones

### 1. **Session cookies no funcionan después de pull**

**Síntoma:** Login exitoso pero redirige inmediatamente al login (401 Unauthorized)

**Causa:** El archivo `login.php` tiene `cookie_domain = 'localhost'` en lugar de vacío

**Solución:**
```bash
# Corregir cookie_domain a vacío
sed -i "s/cookie_domain', 'localhost'/cookie_domain', ''/" /var/www/sisee/api/login.php

# Verificar el cambio
grep "cookie_domain" /var/www/sisee/api/login.php

# Debe mostrar: ini_set('session.cookie_domain', '');
```

**⚠️ Importante:** Después de corregir, los usuarios deben cerrar sesión y volver a iniciar sesión para que se generen nuevas cookies con el dominio correcto.

### 2. **Backend Python no inicia o da "Address already in use"**

**Síntoma:** Error al iniciar uvicorn o clasificador no responde

**Solución:**
```bash
# Matar todos los procesos de uvicorn
pkill -9 -f uvicorn

# Verificar que el puerto 8000 esté libre
ss -tulnp | grep :8000
# No debe mostrar nada

# Iniciar backend correctamente
cd /var/www/sisee/backend
nohup python3 -m uvicorn main:app --host 127.0.0.1 --port 8000 --reload > /var/log/uvicorn.log 2>&1 &

# Verificar que inició
sleep 3
ps aux | grep uvicorn | grep -v grep
tail -10 /var/log/uvicorn.log
```

### 3. **Clasificador da timeout o conexión rechazada**

**Síntoma:** `ERR_CONNECTION_TIMED_OUT` al usar clasificador desde el navegador

**Causa:** El frontend intenta conectarse directamente al puerto 8000 que está bloqueado. Debe usar el proxy PHP.

**Solución:**
```bash
# Verificar que .env use el proxy PHP
cat /var/www/sisee/.env

# Debe mostrar:
# VITE_BACKEND_URL=http://50.21.181.205/api/proxy-clasificacion.php

# Si está mal, corregir:
echo 'VITE_API_URL=http://50.21.181.205/api' > /var/www/sisee/.env
echo 'VITE_BACKEND_URL=http://50.21.181.205/api/proxy-clasificacion.php' >> /var/www/sisee/.env

# SIEMPRE recompilar después de cambiar .env
cd /var/www/sisee
npm run build

# Probar el proxy
curl -s 'http://localhost/api/proxy-clasificacion.php?path=clasificacion/categorias'
```

### 4. **Cambios del frontend no se ven**

**Síntoma:** Código actualizado pero navegador muestra versión antigua

**Solución:**
```bash
# Limpiar caché de build
rm -rf /var/www/sisee/dist/*

# Reconstruir
cd /var/www/sisee
npm run build

# En el navegador:
# - Presionar Ctrl+Shift+R (recarga sin caché)
# - O limpiar caché del navegador manualmente
```

### 5. **Error de permisos**

**Síntoma:** Git pull falla por permisos

**Solución:**
```bash
# Verificar propietario de archivos
ls -la /var/www/sisee

# Ajustar permisos si es necesario
sudo chown -R root:root /var/www/sisee
sudo chmod -R 755 /var/www/sisee
```

---

## 📝 Checklist de Despliegue

Usa esta lista para verificar cada despliegue:

- [ ] ✅ Commit y push realizados desde local
- [ ] ✅ Conectado al VPS por SSH
- [ ] ✅ Git pull ejecutado sin errores
- [ ] ✅ `.env` configurado con proxy PHP (NO puerto 8000 directo)
- [ ] ✅ `login.php` tiene `cookie_domain` vacío (NO 'localhost')
- [ ] ✅ npm install ejecutado (si hay cambios en package.json)
- [ ] ✅ npm run build ejecutado correctamente
- [ ] ✅ Backend Python corriendo: `ps aux | grep uvicorn`
- [ ] ✅ Logs del backend sin errores: `tail /var/log/uvicorn.log`
- [ ] ✅ Proxy PHP funciona: `curl http://localhost/api/proxy-clasificacion.php?path=clasificacion/categorias`
- [ ] ✅ Frontend carga correctamente en http://50.21.181.205
- [ ] ✅ Login funciona correctamente (cerrar sesión y reiniciar)
- [ ] ✅ Clasificador IA funciona correctamente
- [ ] ✅ No hay errores en la consola del navegador (F12)

---

## 🚨 En caso de emergencia

Si algo sale mal y necesitas revertir cambios:

```bash
# Ver últimos commits
git log --oneline -5

# Revertir al commit anterior
git reset --hard HEAD~1

# O revertir a un commit específico
git reset --hard <commit-hash>

# Forzar push (solo si es necesario)
git push --force
```

---

## 📞 Contactos y URLs

- **VPS IP:** 50.21.181.205
- **Frontend:** http://50.21.181.205
- **Backend Python:** http://127.0.0.1:8000 (solo interno, vía proxy PHP)
- **Proxy Backend:** http://50.21.181.205/api/proxy-clasificacion.php
- **API PHP:** http://50.21.181.205/api

---

## 🔐 Variables de Entorno

### Local (.env)
```env
VITE_API_URL=http://localhost/SISEE/api
VITE_BACKEND_URL=http://127.0.0.1:8000
```

### Producción (VPS)
```env
VITE_API_URL=http://50.21.181.205/api
VITE_BACKEND_URL=http://50.21.181.205/api/proxy-clasificacion.php
```

**⚠️ CRÍTICO:** 
- El archivo `.env` en el VPS DEBE usar el **proxy PHP**, NO el puerto 8000 directo
- El proxy PHP (`proxy-clasificacion.php`) redirige internamente a `127.0.0.1:8000`
- Esto evita problemas de firewall y CORS

**Cómo configurar en VPS:**
```bash
# Crear/actualizar .env en el VPS
cd /var/www/sisee
echo 'VITE_API_URL=http://50.21.181.205/api' > .env
echo 'VITE_BACKEND_URL=http://50.21.181.205/api/proxy-clasificacion.php' >> .env

# SIEMPRE recompilar después de cambiar .env
npm run build
```

**Arquitectura del clasificador:**
```
Navegador → http://50.21.181.205/api/proxy-clasificacion.php?path=clasificacion/categorias
              ↓
          Apache (puerto 80)
              ↓
          proxy-clasificacion.php
              ↓
          http://127.0.0.1:8000/clasificacion/categorias
              ↓
          FastAPI Backend (uvicorn)
```

---

*Última actualización: Enero 2026*
