-- =====================================================
-- ÍNDICES DE RENDIMIENTO PARA PETICIONES
-- =====================================================
-- Estos índices mejoran significativamente el rendimiento
-- de las queries principales del sistema de peticiones
-- =====================================================

-- 1. Índice compuesto para la query principal de peticiones
-- Mejora el ordenamiento y filtrado por fecha_registro
ALTER TABLE peticiones 
ADD INDEX idx_fecha_estado (fecha_registro DESC, estado);

-- 2. Índice para filtros por división (municipio)
ALTER TABLE peticiones 
ADD INDEX idx_division_fecha (division_id, fecha_registro DESC);

-- 3. Índice para búsqueda por folio (búsquedas LIKE)
ALTER TABLE peticiones 
ADD INDEX idx_folio (folio);

-- 4. Índice para búsqueda por nombre (búsquedas LIKE)
ALTER TABLE peticiones 
ADD INDEX idx_nombre (nombre(100));

-- 5. Índice para filtro por nivel de importancia
ALTER TABLE peticiones 
ADD INDEX idx_nivel_importancia (NivelImportancia);

-- 6. Índice para filtro por usuario de seguimiento
ALTER TABLE peticiones 
ADD INDEX idx_usuario (usuario_id);

-- 7. Índice compuesto para peticion_departamento (optimiza JOINs)
ALTER TABLE peticion_departamento 
ADD INDEX idx_peticion_departamento (peticion_id, departamento_id);

-- 8. Índice para búsqueda inversa (departamento -> peticiones)
ALTER TABLE peticion_departamento 
ADD INDEX idx_departamento_peticion (departamento_id, peticion_id);

-- 9. Índice para peticion_sugerencias (optimiza JOINs)
ALTER TABLE peticion_sugerencias 
ADD INDEX idx_peticion_sugerencia (peticion_id, fecha_sugerencia DESC);

-- 10. Índice para Usuario (mejora JOIN con peticiones)
ALTER TABLE Usuario 
ADD INDEX idx_usuario_nombre (Id, Nombre, ApellidoP, ApellidoM);

-- =====================================================
-- VERIFICAR ÍNDICES EXISTENTES
-- =====================================================
-- Para ver qué índices ya existen en una tabla:
-- SHOW INDEX FROM peticiones;
-- SHOW INDEX FROM peticion_departamento;
-- SHOW INDEX FROM peticion_sugerencias;

-- =====================================================
-- NOTAS IMPORTANTES:
-- =====================================================
-- 1. Ejecutar este script UNA SOLA VEZ
-- 2. Si hay errores de "Duplicate key name", significa que
--    el índice ya existe (esto es normal en re-ejecuciones)
-- 3. Después de crear índices, ejecutar:
--    ANALYZE TABLE peticiones;
--    ANALYZE TABLE peticion_departamento;
--    ANALYZE TABLE peticion_sugerencias;
-- 4. Para tablas grandes (>10,000 registros), crear índices
--    puede tomar varios segundos
-- =====================================================
