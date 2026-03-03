# 🔧 Guía de Ejecución en DBeaver - Migraciones de Notificaciones

## ⚠️ Problema Común: DELIMITER no funciona en DBeaver

DBeaver (y otros clientes GUI) **NO soportan** la directiva `DELIMITER` porque es específica del cliente `mysql` de línea de comandos, no del servidor MySQL.

---

## ✅ Solución: Ejecutar por Bloques

### Método 1: Archivo Principal Actualizado (Recomendado)

El archivo `migraciones_notificaciones.sql` ya está corregido. Ejecuta así:

1. **Abrir el archivo** en DBeaver
2. **Ejecutar por secciones** numeradas (1-8)
3. **Para la sección 7 (Trigger):**
   - Primero ejecuta: `DROP TRIGGER IF EXISTS trg_crear_config_notificacion_rol9;`
   - Luego selecciona TODO el bloque `CREATE TRIGGER...` hasta el punto y coma final
   - Ejecuta el bloque completo con `Ctrl+Enter`

### Método 2: Archivo Separado del Trigger

Si tienes problemas con el trigger:

1. **Abre:** `database/trigger_notificaciones_separado.sql`
2. **Ejecuta Statement 1:** `DROP TRIGGER IF EXISTS...`
3. **Selecciona TODO el Statement 2** (el bloque completo CREATE TRIGGER)
4. **Ejecuta:** `Ctrl+Enter` o botón "Execute SQL Statement"

---

## 📋 Orden de Ejecución Completo

### 1️⃣ Columnas de Email en Usuario
```sql
ALTER TABLE `Usuario` 
ADD COLUMN IF NOT EXISTS `Email` VARCHAR(255) NULL 
    COMMENT 'Correo electrónico del usuario' AFTER `Password`;

ALTER TABLE `Usuario` 
ADD COLUMN IF NOT EXISTS `EmailVerificado` TINYINT(1) DEFAULT 0 
    COMMENT 'Indica si el email ha sido verificado' AFTER `Email`;

CREATE INDEX IF NOT EXISTS idx_usuario_email ON Usuario(Email);
```

### 2️⃣ Tabla NotificacionConfiguracion
```sql
CREATE TABLE IF NOT EXISTS `NotificacionConfiguracion` (
  `Id` INT(11) NOT NULL AUTO_INCREMENT,
  -- ... resto de la definición
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### 3️⃣ Tabla NotificacionHistorial
```sql
CREATE TABLE IF NOT EXISTS `NotificacionHistorial` (
  `Id` INT(11) NOT NULL AUTO_INCREMENT,
  -- ... resto de la definición
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### 4️⃣ Vista de Peticiones Pendientes
```sql
CREATE OR REPLACE VIEW vista_peticiones_pendientes_departamento AS
SELECT 
    u.id AS departamento_id,
    -- ... resto de la vista
FROM unidades u
-- ... resto del query
```

### 5️⃣ Índices Adicionales
```sql
CREATE INDEX IF NOT EXISTS idx_dept_estado 
ON peticion_departamento(departamento_id, estado);

CREATE INDEX IF NOT EXISTS idx_fecha_asignacion 
ON peticion_departamento(fecha_asignacion);
```

### 6️⃣ Insertar Configuraciones por Defecto
```sql
INSERT INTO NotificacionConfiguracion 
(IdUsuario, IdUnidad, NotificacionesActivas, FiltrarPorMunicipio)
SELECT 
    u.Id,
    u.IdUnidad,
    0,
    0
FROM Usuario u
INNER JOIN UsuarioRol ur ON u.Id = ur.IdUsuario
WHERE ur.IdRolSistema = 9
  AND u.IdUnidad IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM NotificacionConfiguracion nc WHERE nc.IdUsuario = u.Id
  );
```

### 7️⃣ Trigger (⚠️ Especial)

**Paso A - Eliminar trigger anterior:**
```sql
DROP TRIGGER IF EXISTS trg_crear_config_notificacion_rol9;
```

**Paso B - Crear trigger nuevo:**

✅ **IMPORTANTE:** Selecciona TODO el bloque de una vez, desde `CREATE` hasta el punto y coma final.

```sql
CREATE TRIGGER trg_crear_config_notificacion_rol9
AFTER INSERT ON UsuarioRol
FOR EACH ROW
BEGIN
    DECLARE v_unidad_id INT;
    
    IF NEW.IdRolSistema = 9 THEN
        SELECT IdUnidad INTO v_unidad_id 
        FROM Usuario 
        WHERE Id = NEW.IdUsuario;
        
        IF v_unidad_id IS NOT NULL THEN
            INSERT IGNORE INTO NotificacionConfiguracion 
                (IdUsuario, IdUnidad, NotificacionesActivas, FiltrarPorMunicipio)
            VALUES 
                (NEW.IdUsuario, v_unidad_id, 0, 0);
        END IF;
    END IF;
END;
```

### 8️⃣ Verificación
```sql
-- Verificar columnas y tablas creadas
SELECT 
    'Usuario.Email' as Elemento,
    IF(COUNT(*) > 0, '✅ Columna existe', '❌ Columna no existe') as Estado
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'sisegestion' 
  AND TABLE_NAME = 'Usuario' 
  AND COLUMN_NAME = 'Email'
UNION ALL
SELECT 
    'NotificacionConfiguracion',
    IF(COUNT(*) > 0, '✅ Tabla existe', '❌ Tabla no existe')
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'sisegestion' 
  AND TABLE_NAME = 'NotificacionConfiguracion'
-- ... resto de verificaciones
```

---

## 🔍 Cómo Ejecutar en DBeaver

### Opción A: Selección + Ejecutar

1. Selecciona el texto SQL que quieres ejecutar
2. Presiona `Ctrl + Enter` (Windows/Linux) o `Cmd + Enter` (Mac)
3. O haz clic derecho → "Execute SQL Statement"

### Opción B: Ejecutar todo el script

1. Abre el archivo SQL
2. Presiona `Ctrl + X` (Execute Script - ejecuta todo)
3. ⚠️ Esto puede fallar en el trigger, usa Opción A para esa sección

### Opción C: Desde terminal (MySQL CLI)

Si DBeaver te causa problemas, usa la línea de comandos:

```bash
# Cambiar a directorio de la base de datos
cd C:\xampp\htdocs\SISEE\database

# Ejecutar migraciones completas
mysql -u siseg -p sisegestion < migraciones_notificaciones.sql

# O solo el trigger si ya ejecutaste el resto
mysql -u siseg -p sisegestion < trigger_notificaciones_separado.sql
```

---

## ❓ Preguntas Frecuentes

### ¿Por qué el error "Syntax error near DELIMITER"?

**Causa:** DBeaver no reconoce `DELIMITER` porque es una directiva del cliente CLI.

**Solución:** Usa los archivos actualizados que no tienen `DELIMITER`.

### ¿Por qué "Syntax error near BEGIN"?

**Causa:** Estás ejecutando línea por línea el trigger.

**Solución:** Selecciona TODO el bloque `CREATE TRIGGER ... END;` completo y ejecútalo de una vez.

### ¿Cómo verifico si el trigger se creó?

```sql
SHOW TRIGGERS WHERE `Trigger` = 'trg_crear_config_notificacion_rol9';
```

Deberías ver una fila con el trigger.

### ¿Qué pasa si ya ejecuté parte de las migraciones?

No hay problema. Todos los statements usan `IF NOT EXISTS` o `CREATE OR REPLACE`, así que:
- No fallarán si ya existen
- No duplicarán datos
- Son seguros para re-ejecutar

---

## ✅ Checklist de Verificación

Después de ejecutar todo, verifica:

- [ ] Columna `Usuario.Email` existe
- [ ] Tabla `NotificacionConfiguracion` existe
- [ ] Tabla `NotificacionHistorial` existe
- [ ] Vista `vista_peticiones_pendientes_departamento` existe
- [ ] Trigger `trg_crear_config_notificacion_rol9` existe
- [ ] Hay registros en `NotificacionConfiguracion` para usuarios con Rol 9

---

## 🆘 Si Nada Funciona

Si sigues teniendo problemas en DBeaver:

1. **Guarda tu trabajo**
2. **Cierra DBeaver**
3. **Usa MySQL Workbench** o **línea de comandos**:

```bash
# Windows (desde cmd o PowerShell)
cd C:\xampp\mysql\bin
mysql.exe -u siseg -p sisegestion < C:\xampp\htdocs\SISEE\database\migraciones_notificaciones.sql
```

4. **Reabrir DBeaver** y continuar normalmente

---

## 📞 Soporte

Si los archivos SQL actualizados aún causan problemas, reporta:
- Versión de DBeaver
- Mensaje de error exacto
- Qué statement estás ejecutando
