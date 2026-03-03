-- ================================================================================
-- TRIGGER PARA NOTIFICACIONES - EJECUCIÓN SEPARADA
-- ================================================================================
-- Este archivo contiene solo el trigger, separado para facilitar su ejecución
-- en DBeaver u otros clientes SQL que tienen problemas con DELIMITER
--
-- INSTRUCCIONES PARA DBEAVER:
-- ================================================================================
--
-- OPCIÓN 1 (Recomendada):
-- 1. Abre este archivo en DBeaver
-- 2. Selecciona TODO el texto del Statement 2 (desde CREATE hasta el punto y coma final)
-- 3. Click derecho → "Execute SQL Statement" (Ctrl+Enter)
-- 4. Verificar que se creó: SHOW TRIGGERS LIKE 'trg_crear_config_notificacion_rol9';
--
-- OPCIÓN 2 (Si la Opción 1 falla):
-- 1. Ejecuta cada statement por separado
-- 2. Primero ejecuta el Statement 1
-- 3. Luego ejecuta el Statement 2 completo
--
-- OPCIÓN 3 (Desde línea de comandos MySQL):
-- mysql -u siseg -p sisegestion < trigger_notificaciones_separado.sql
--
-- ================================================================================

USE sisegestion;

-- ================================================================================
-- STATEMENT 1: Eliminar trigger existente
-- ================================================================================
-- Ejecuta esta línea primero para limpiar cualquier versión anterior

DROP TRIGGER IF EXISTS trg_crear_config_notificacion_rol9;

-- ================================================================================
-- STATEMENT 2: Crear trigger
-- ================================================================================
-- IMPORTANTE: Selecciona y ejecuta COMPLETO desde CREATE hasta el punto y coma
-- NO ejecutes línea por línea, debe ser TODO EL BLOQUE JUNTO

CREATE TRIGGER trg_crear_config_notificacion_rol9
AFTER INSERT ON UsuarioRol
FOR EACH ROW
BEGIN
    DECLARE v_unidad_id INT;
    
    -- Solo ejecutar si el rol asignado es 9 (Gestor de Departamento)
    IF NEW.IdRolSistema = 9 THEN
        -- Obtener la unidad del usuario
        SELECT IdUnidad INTO v_unidad_id 
        FROM Usuario 
        WHERE Id = NEW.IdUsuario;
        
        -- Si tiene unidad asignada y no tiene configuración, crearla
        IF v_unidad_id IS NOT NULL THEN
            INSERT IGNORE INTO NotificacionConfiguracion 
                (IdUsuario, IdUnidad, NotificacionesActivas, FiltrarPorMunicipio)
            VALUES 
                (NEW.IdUsuario, v_unidad_id, 0, 0);
        END IF;
    END IF;
END;

-- ================================================================================
-- VERIFICACIÓN: Comprobar que el trigger se creó correctamente
-- ================================================================================
-- Ejecuta esta consulta para verificar que el trigger existe

SHOW TRIGGERS WHERE `Trigger` = 'trg_crear_config_notificacion_rol9';

-- ================================================================================
-- PRUEBA: Verificar funcionamiento del trigger
-- ================================================================================
-- Este bloque es opcional, solo para probar que el trigger funciona
-- Comentado por defecto para no crear datos de prueba

/*
-- Crear usuario de prueba
INSERT INTO Usuario (Usuario, Nombre, Password, IdUnidad) 
VALUES ('usuario_prueba_trigger', 'Prueba Trigger', 'test123', 1);

-- Obtener ID del usuario recién creado
SET @test_user_id = LAST_INSERT_ID();

-- Asignar rol 9 (esto debe activar el trigger)
INSERT INTO UsuarioRol (IdUsuario, IdRolSistema) 
VALUES (@test_user_id, 9);

-- Verificar que se creó la configuración automáticamente
SELECT nc.* 
FROM NotificacionConfiguracion nc
WHERE nc.IdUsuario = @test_user_id;

-- Limpiar datos de prueba
DELETE FROM UsuarioRol WHERE IdUsuario = @test_user_id;
DELETE FROM Usuario WHERE Id = @test_user_id;
*/

-- ================================================================================
-- NOTAS TÉCNICAS
-- ================================================================================
--
-- ¿Por qué no funciona DELIMITER en DBeaver?
-- --------------------------------------
-- DELIMITER es una directiva del cliente mysql CLI, NO una instrucción SQL.
-- DBeaver y otros clientes GUI no la reconocen porque procesan SQL directamente.
-- Por eso este archivo no usa DELIMITER.
--
-- ¿Qué hace este trigger?
-- -----------------------
-- Cuando se asigna el Rol 9 (Gestor de Departamento) a un usuario:
-- 1. Verifica que el usuario tenga una unidad asignada (IdUnidad)
-- 2. Crea automáticamente una configuración de notificaciones
-- 3. La configuración se crea DESACTIVADA por defecto (NotificacionesActivas = 0)
-- 4. El usuario puede activarla después desde el frontend
--
-- ¿Por qué usar INSERT IGNORE?
-- ----------------------------
-- Si ya existe una configuración para ese usuario, no la sobrescribe.
-- Evita errores de clave duplicada.
--
-- ================================================================================
