-- =====================================================
-- VERIFICACIÓN DE RENDIMIENTO
-- =====================================================
-- Script para analizar y verificar el rendimiento
-- del sistema de peticiones después de optimizaciones
-- =====================================================

-- 1. VERIFICAR ÍNDICES CREADOS
-- =====================================================
SELECT 
    TABLE_NAME as 'Tabla',
    INDEX_NAME as 'Índice',
    COLUMN_NAME as 'Columna',
    SEQ_IN_INDEX as 'Orden',
    CARDINALITY as 'Cardinalidad'
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('peticiones', 'peticion_departamento', 'peticion_sugerencias', 'Usuario')
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;

-- =====================================================
-- 2. ANALIZAR QUERY PRINCIPAL (sin ejecutarla)
-- =====================================================
EXPLAIN 
SELECT 
    p.*,
    p.division_id,
    d.Municipio as nombre_municipio,
    u.Nombre as nombre_usuario_seguimiento,
    u.ApellidoP as apellido_paterno_usuario,
    u.ApellidoM as apellido_materno_usuario,
    CONCAT(
        u.Nombre, 
        CASE 
            WHEN u.ApellidoP IS NOT NULL AND u.ApellidoP != '' THEN CONCAT(' ', u.ApellidoP) 
            ELSE '' 
        END,
        CASE 
            WHEN u.ApellidoM IS NOT NULL AND u.ApellidoM != '' THEN CONCAT(' ', u.ApellidoM) 
            ELSE '' 
        END
    ) as nombre_completo_usuario,
    GROUP_CONCAT(DISTINCT CONCAT_WS('||', pd.id, pd.departamento_id, pd.estado, pd.fecha_asignacion, un.nombre_unidad) SEPARATOR '@@') as departamentos_data,
    GROUP_CONCAT(DISTINCT CONCAT_WS('||', ps.id, ps.departamento_nombre, ps.estado_sugerencia, ps.fecha_sugerencia) SEPARATOR '@@') as sugerencias_data
FROM peticiones p
LEFT JOIN Usuario u ON p.usuario_id = u.Id
LEFT JOIN DivisionAdministrativa d ON p.division_id = d.Id
LEFT JOIN peticion_departamento pd ON p.id = pd.peticion_id
LEFT JOIN unidades un ON pd.departamento_id = un.id
LEFT JOIN peticion_sugerencias ps ON p.id = ps.peticion_id
GROUP BY p.id 
ORDER BY p.fecha_registro DESC;

-- =====================================================
-- INTERPRETAR RESULTADO DE EXPLAIN:
-- =====================================================
-- ✅ BUENO:
--    - type: ALL, index, range, ref, eq_ref
--    - key: nombre del índice usado
--    - rows: número bajo (<1000 idealmente)
--    - Extra: "Using index" es excelente
--
-- ❌ MALO:
--    - type: ALL sin índice
--    - key: NULL (no usa índice)
--    - rows: número muy alto
--    - Extra: "Using filesort", "Using temporary"
-- =====================================================

-- 3. ESTADÍSTICAS DE TABLAS
-- =====================================================
SELECT 
    TABLE_NAME as 'Tabla',
    TABLE_ROWS as 'Filas Aprox',
    AVG_ROW_LENGTH as 'Tamaño Fila',
    ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) as 'Tamaño Total (MB)',
    ROUND(DATA_LENGTH / 1024 / 1024, 2) as 'Datos (MB)',
    ROUND(INDEX_LENGTH / 1024 / 1024, 2) as 'Índices (MB)',
    AUTO_INCREMENT as 'Próximo ID'
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('peticiones', 'peticion_departamento', 'peticion_sugerencias')
ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC;

-- 4. VERIFICAR FRAGMENTACIÓN
-- =====================================================
SELECT 
    TABLE_NAME as 'Tabla',
    ROUND(DATA_FREE / 1024 / 1024, 2) as 'Espacio Libre (MB)',
    ROUND((DATA_FREE / (DATA_LENGTH + INDEX_LENGTH + DATA_FREE)) * 100, 2) as 'Fragmentación %'
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('peticiones', 'peticion_departamento', 'peticion_sugerencias')
  AND DATA_FREE > 0;

-- Si fragmentación > 10%, ejecutar:
-- OPTIMIZE TABLE peticiones;
-- OPTIMIZE TABLE peticion_departamento;
-- OPTIMIZE TABLE peticion_sugerencias;

-- 5. CONTADORES Y ESTADÍSTICAS
-- =====================================================
SELECT 
    COUNT(*) as 'Total Peticiones',
    COUNT(DISTINCT division_id) as 'Municipios Distintos',
    COUNT(DISTINCT usuario_id) as 'Usuarios con Seguimiento',
    MIN(fecha_registro) as 'Primera Petición',
    MAX(fecha_registro) as 'Última Petición'
FROM peticiones;

SELECT 
    COUNT(*) as 'Total Asignaciones Departamentos',
    COUNT(DISTINCT peticion_id) as 'Peticiones con Departamentos',
    COUNT(DISTINCT departamento_id) as 'Departamentos Distintos'
FROM peticion_departamento;

SELECT 
    COUNT(*) as 'Total Sugerencias IA',
    COUNT(DISTINCT peticion_id) as 'Peticiones con Sugerencias'
FROM peticion_sugerencias;

-- 6. PETICIONES POR ESTADO (para caché)
-- =====================================================
SELECT 
    estado,
    COUNT(*) as cantidad,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM peticiones), 2) as porcentaje
FROM peticiones
GROUP BY estado
ORDER BY cantidad DESC;

-- 7. DISTRIBUCIÓN POR NIVEL DE IMPORTANCIA
-- =====================================================
SELECT 
    NivelImportancia as nivel,
    CASE NivelImportancia
        WHEN 1 THEN 'Muy Alta'
        WHEN 2 THEN 'Alta'
        WHEN 3 THEN 'Media'
        WHEN 4 THEN 'Baja'
        WHEN 5 THEN 'Muy Baja'
        ELSE 'No definida'
    END as descripcion,
    COUNT(*) as cantidad
FROM peticiones
GROUP BY NivelImportancia
ORDER BY NivelImportancia;

-- 8. PETICIONES MÁS ANTIGUAS SIN PROCESAR
-- =====================================================
SELECT 
    id,
    folio,
    nombre,
    estado,
    fecha_registro,
    TIMESTAMPDIFF(HOUR, fecha_registro, NOW()) as horas_esperando
FROM peticiones
WHERE estado IN ('Sin revisar', 'Rechazado por departamento', 'Por asignar departamento')
ORDER BY fecha_registro ASC
LIMIT 10;

-- 9. RENDIMIENTO DE QUERIES (si está habilitado slow query log)
-- =====================================================
-- Para habilitar temporalmente:
-- SET GLOBAL slow_query_log = 'ON';
-- SET GLOBAL long_query_time = 1; -- Queries > 1 segundo
-- SET GLOBAL slow_query_log_file = '/var/log/mysql/slow.log';

-- 10. VERIFICAR CONFIGURACIÓN DE MYSQL
-- =====================================================
SHOW VARIABLES LIKE 'innodb_buffer_pool_size';
SHOW VARIABLES LIKE 'max_connections';
SHOW VARIABLES LIKE 'query_cache%';
SHOW VARIABLES LIKE 'tmp_table_size';
SHOW VARIABLES LIKE 'max_heap_table_size';

-- =====================================================
-- RECOMENDACIONES DE CONFIGURACIÓN MYSQL (my.ini/my.cnf)
-- =====================================================
-- Para mejor rendimiento con 60-1000 peticiones:
--
-- [mysqld]
-- innodb_buffer_pool_size = 256M    # 25% RAM disponible
-- max_connections = 150             # Ajustar según uso
-- query_cache_size = 16M            # Si MySQL < 8.0
-- query_cache_type = 1              # Si MySQL < 8.0
-- tmp_table_size = 64M
-- max_heap_table_size = 64M
-- innodb_flush_log_at_trx_commit = 2
-- innodb_log_buffer_size = 8M
-- =====================================================
