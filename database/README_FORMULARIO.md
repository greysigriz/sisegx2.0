# Configuración del Permiso "Formulario"

## ✅ Cambios realizados

### 1. Base de datos
- **Archivo SQL**: `database/permiso_formulario.sql`
- **Permiso creado**: `peticiones_formulario`
- **Descripción**: "Permite acceder al formulario para crear nuevas peticiones ciudadanas"
- **Módulo**: Peticiones

### 2. Frontend
- **Sidebar actualizado**: Agregado item "Crear Petición" con ícono de edición
- **Ruta**: `/petition`
- **Permiso requerido**: `peticiones_formulario`
- **Router actualizado**: La ruta `/petition` ahora requiere autenticación

## 📝 Pasos para activar

### 1. Ejecutar el SQL
Abre phpMyAdmin o tu cliente MySQL y ejecuta:
```bash
# En phpMyAdmin:
# - Selecciona la base de datos "sisegestion"
# - Ve a la pestaña SQL
# - Copia y pega el contenido de: database/permiso_formulario.sql
# - Haz clic en "Continuar"
```

### 2. Verificar la creación
El script incluye verificaciones automáticas que mostrarán:
- ✅ Si el rol "Formulario" existe
- ✅ La asignación del permiso al rol
- ✅ Lista de todos los permisos del rol "Formulario"

### 3. Asignar el rol a usuarios
Para que un usuario pueda ver el formulario:

**Opción A - Desde la interfaz:**
1. Inicia sesión con un usuario administrador
2. Ve a **Configuración → Usuarios**
3. Edita el usuario al que quieres dar acceso
4. Marca el checkbox del rol "Formulario"
5. Guarda los cambios

**Opción B - Desde SQL:**
```sql
-- Asignar rol "Formulario" al usuario con ID 5
INSERT INTO UsuarioRol (IdUsuario, IdRolSistema)
VALUES (5, (SELECT Id FROM RolSistema WHERE Nombre = 'Formulario'));
```

### 4. Cerrar sesión y volver a iniciar
Para que los cambios se apliquen:
1. El usuario debe cerrar sesión
2. Volver a iniciar sesión
3. Ahora verá el item "Crear Petición" en el menú

## 🎯 Resultado esperado

Usuarios con el rol "Formulario" verán en el menú:
```
📊 Bienvenido
✏️ Crear Petición  ← NUEVO
```

Al hacer clic en "Crear Petición":
- Se abrirá el formulario completo de peticiones ciudadanas
- Podrán llenar todos los campos
- Enviar la petición
- Obtener un folio de seguimiento

## 🔒 Seguridad

- ✅ La ruta `/petition` ahora requiere autenticación
- ✅ Solo usuarios con el permiso `peticiones_formulario` pueden acceder
- ✅ El Sidebar filtra automáticamente los items según permisos del usuario

## 📋 Próximos pasos (opcional)

Para los demás roles que mencionaste:
- **Canalizador Municipal**: Necesitarás definir qué permisos adicionales tiene
- **Canalizador Estatal**: Igual que el anterior
- Puedes ejecutar el mismo proceso para asignarles el permiso `peticiones_formulario` si también deben crear peticiones

## 🐛 Troubleshooting

**Problema**: No aparece el item en el menú después de asignar el rol
**Solución**:
1. Verifica que el usuario tenga el rol asignado en `UsuarioRol`
2. Cierra sesión completamente
3. Vuelve a iniciar sesión
4. Revisa la consola del navegador (F12) para ver si hay errores

**Problema**: Error 403 al intentar acceder
**Solución**:
1. Verifica que el permiso esté en la tabla `RolPermiso`
2. Verifica que el usuario tenga el rol correcto
3. Revisa que `login.php` esté cargando los permisos correctamente
