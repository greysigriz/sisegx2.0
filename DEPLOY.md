# 🚀 Guía de Despliegue al VPS

Esta guía explica cómo subir cambios al VPS de producción de manera segura y correcta.

---

## 📋 Pre-requisitos

- Git configurado y cambios commiteados
- Acceso SSH al VPS: `root@50.21.181.205`
- Backend Python corriendo en el VPS (puerto 8000)
- Apache configurado en el VPS

---

## 🔄 Proceso de Despliegue

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

# Hacer pull de los cambios
git pull
```

**⚠️ Si hay conflictos con archivos locales:**
```bash
# Guardar cambios locales temporalmente (especialmente .env)
git stash

# Hacer pull
git pull

# Restaurar .env si fue guardado
git stash pop
```

**🔧 Verificar .env después del pull:**
```bash
# El .env DEBE tener las URLs del VPS, NO localhost
cat .env

# Si está mal, corregir:
echo 'VITE_API_URL=http://50.21.181.205/api' > .env
echo 'VITE_BACKEND_URL=http://50.21.181.205:8000' >> .env
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

# Instalar dependencias si hay cambios en requirements.txt
pip3 install -r requirements.txt

# Verificar si uvicorn está corriendo
ps aux | grep uvicorn

# Si NO está corriendo, iniciarlo:
cd /var/www/sisee/backend
nohup python3 -m uvicorn main:app --host 0.0.0.0 --port 8000 --reload > /var/log/uvicorn.log 2>&1 &

# Si SÍ está corriendo, reiniciarlo:
pkill -f uvicorn
cd /var/www/sisee/backend
nohup python3 -m uvicorn main:app --host 0.0.0.0 --port 8000 --reload > /var/log/uvicorn.log 2>&1 &
```

**✅ Verificación del Backend:**
```bash
# Ver el log del backend
tail -f /var/log/uvicorn.log

# Verificar que esté corriendo
curl http://localhost:8000/clasificacion/categorias
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

# Reiniciar Backend Python
pkill -f uvicorn
cd /var/www/sisee/backend && nohup python3 -m uvicorn main:app --host 0.0.0.0 --port 8000 --reload > /var/log/uvicorn.log 2>&1 &
```

---

## ⚠️ Problemas Comunes y Soluciones

### 1. **Session cookies no funcionan después de pull**

**Síntoma:** Login exitoso pero redirige inmediatamente al login

**Solución:**
```bash
# Verificar configuración de cookies en login.php
grep "cookie_domain" /var/www/sisee/api/login.php

# Debe estar vacío: ini_set('session.cookie_domain', '');
# Si está en 'localhost', corregirlo:
sed -i '427s/localhost//' /var/www/sisee/api/login.php
```

### 2. **Backend Python no inicia**

**Síntoma:** Error 404 o CORS al clasificar

**Solución:**
```bash
# Verificar que uvicorn esté instalado
pip3 list | grep uvicorn

# Si no está, instalar
pip3 install uvicorn

# Verificar dependencias
cd /var/www/sisee/backend
pip3 install -r requirements.txt

# Iniciar backend
python3 -m uvicorn main:app --host 0.0.0.0 --port 8000 --reload
```

### 3. **Cambios del frontend no se ven**

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
# - O limpiar caché del navegador
```

### 4. **Error de permisos**

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
- [ ] ✅ npm install ejecutado (si hay cambios en package.json)
- [ ] ✅ npm run build ejecutado correctamente
- [ ] ✅ Backend Python corriendo (ps aux | grep uvicorn)
- [ ] ✅ Logs del backend sin errores (tail /var/log/uvicorn.log)
- [ ] ✅ Frontend carga correctamente en http://50.21.181.205
- [ ] ✅ Login funciona correctamente
- [ ] ✅ Clasificador IA funciona correctamente
- [ ] ✅ No hay errores en la consola del navegador

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
- **Backend Python:** http://50.21.181.205:8000
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
VITE_BACKEND_URL=http://50.21.181.205:8000
```

**⚠️ IMPORTANTE:** El archivo `.env` en el VPS DEBE usar la IP pública (50.21.181.205), NO localhost ni 127.0.0.1

**Cómo configurar en VPS:**
```bash
# Crear/actualizar .env en el VPS
ssh root@50.21.181.205
cd /var/www/sisee
echo 'VITE_API_URL=http://50.21.181.205/api' > .env
echo 'VITE_BACKEND_URL=http://50.21.181.205:8000' >> .env

# SIEMPRE recompilar después de cambiar .env
npm run build
```

Las variables de entorno del VPS se aplican:
- Frontend: Durante `npm run build` (se compilan en el JavaScript)
- Backend Python: En main.py (host 0.0.0.0 para aceptar conexiones externas)
- Apache: Sirve los archivos estáticos de dist/

**⚠️ Nota:** Después de cambiar .env, SIEMPRE hacer `npm run build` para que los cambios se apliquen.

---

*Última actualización: Enero 2026*
