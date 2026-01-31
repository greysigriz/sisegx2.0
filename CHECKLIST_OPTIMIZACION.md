# ✅ CHECKLIST DE OPTIMIZACIÓN

Marca cada item cuando lo completes:

## 📋 Instalación (Hacer UNA sola vez)

- [ ] **1. Ejecutar script de índices**
  - [ ] Abrir phpMyAdmin en `http://localhost/phpmyadmin`
  - [ ] Seleccionar base de datos
  - [ ] Ir a pestaña "SQL"
  - [ ] Copiar contenido de `database/performance_indexes.sql`
  - [ ] Pegar y ejecutar
  - [ ] Ver mensajes de confirmación verdes

- [ ] **2. Analizar tablas**
  ```sql
  ANALYZE TABLE peticiones;
  ANALYZE TABLE peticion_departamento;
  ANALYZE TABLE peticion_sugerencias;
  ANALYZE TABLE Usuario;
  ```
  - [ ] Ejecutar el código SQL arriba
  - [ ] Ver confirmación exitosa

- [ ] **3. Limpiar caché del navegador**
  - [ ] Presionar `Ctrl + Shift + R` (Windows) o `Cmd + Shift + R` (Mac)
  - [ ] O usar `Ctrl + Shift + Delete` y borrar caché

## ✅ Verificación (Comprobar que funciona)

- [ ] **4. Probar carga rápida**
  - [ ] Abrir página de peticiones
  - [ ] Verificar que carga en < 1 segundo
  - [ ] ✅ **Antes:** 3-5 segundos → **Después:** < 1 segundo

- [ ] **5. Probar actualización sin recarga**
  - [ ] Cambiar estado de una petición
  - [ ] Verificar que NO recarga la página completa
  - [ ] Verificar que el cambio es instantáneo (< 0.3 segundos)
  - [ ] ✅ **Antes:** 2-3 segundos con recarga → **Después:** instantáneo

- [ ] **6. Probar asignación de departamentos**
  - [ ] Asignar un departamento a una petición
  - [ ] Verificar que se actualiza inmediatamente
  - [ ] Verificar que NO recarga toda la página

- [ ] **7. Probar cambio de nivel de importancia**
  - [ ] Cambiar nivel de importancia
  - [ ] Verificar actualización instantánea

- [ ] **8. Probar asignación de seguimiento**
  - [ ] Asignar seguimiento a un usuario
  - [ ] Verificar actualización inmediata

## 🔍 Verificación Técnica (Opcional pero recomendado)

- [ ] **9. Verificar índices creados**
  ```sql
  SHOW INDEX FROM peticiones;
  ```
  - [ ] Buscar índices: `idx_fecha_estado`, `idx_division_fecha`, etc.
  - [ ] Deben aparecer al menos 6 índices nuevos

- [ ] **10. Verificar rendimiento de query**
  - [ ] Abrir `database/verificar_rendimiento.sql`
  - [ ] Ejecutar script completo
  - [ ] Revisar resultados

- [ ] **11. Verificar tiempos en DevTools**
  - [ ] Presionar `F12` en el navegador
  - [ ] Ir a pestaña "Network"
  - [ ] Recargar página de peticiones
  - [ ] Buscar petición a `peticiones.php`
  - [ ] Verificar tiempo < 500ms

## 📊 Métricas de Éxito

Después de completar TODO el checklist, debes ver:

### ✅ Carga Inicial
| Métrica | Antes | Después | ¿Cumple? |
|---------|-------|---------|----------|
| Tiempo de carga | 3-5 segundos | < 1 segundo | [ ] |
| Queries ejecutadas | ~121 | 1 | [ ] |
| Datos transferidos | ~500KB | ~150KB | [ ] |

### ✅ Actualizaciones
| Operación | Antes | Después | ¿Cumple? |
|-----------|-------|---------|----------|
| Cambiar estado | 2-3 seg + recarga | < 0.3 seg sin recarga | [ ] |
| Asignar departamento | 2-3 seg + recarga | < 0.5 seg sin recarga | [ ] |
| Cambiar importancia | 2-3 seg + recarga | < 0.3 seg sin recarga | [ ] |
| Asignar seguimiento | 2-3 seg + recarga | < 0.3 seg sin recarga | [ ] |

## 🚨 Si algo NO funciona

Si marcaste [ ] en lugar de [✅] en alguna verificación:

### Problema: Índices no se crearon
**Solución:**
1. Verificar permisos de usuario MySQL
2. Intentar ejecutar índices uno por uno
3. Ver errores específicos en phpMyAdmin

### Problema: Sigue lento
**Soluciones en orden:**
1. [ ] Limpiar caché del navegador COMPLETAMENTE
2. [ ] Ejecutar `OPTIMIZE TABLE` en las 3 tablas principales
3. [ ] Reiniciar Apache y MySQL
4. [ ] Verificar logs de errores: `api/logs/`

### Problema: Errores en consola
**Solución:**
1. Presionar `F12` → Console
2. Copiar errores en rojo
3. Buscar solución en `documentation/OPTIMIZACION_RENDIMIENTO.md`

## 📝 Notas

**Fecha de instalación:** _______________

**Versión de MySQL:** _______________

**Navegador usado:** _______________

**Número de peticiones en BD:** _______________

**Tiempo de carga medido:** _______________

**Mejoras observadas:**
- Carga inicial: ______% más rápido
- Actualizaciones: ______% más rápido
- Experiencia general: [ ] Excelente [ ] Buena [ ] Regular [ ] Mala

## 🎯 Resultado Final

- [ ] **TODO FUNCIONA PERFECTAMENTE** 🎉
  - Carga rápida ✅
  - Actualizaciones instantáneas ✅
  - Sin recargas innecesarias ✅
  - Usuario satisfecho ✅

## 📚 Documentación de Referencia

- Guía rápida: `GUIA_RAPIDA_OPTIMIZACION.md`
- Documentación completa: `documentation/OPTIMIZACION_RENDIMIENTO.md`
- Verificación: `database/verificar_rendimiento.sql`
- Resumen ejecutivo: `OPTIMIZACION_RESUMEN.md`

---

**Estado:** [ ] Pendiente [ ] En Proceso [ ] Completado ✅

**Satisfacción del usuario:** ⭐⭐⭐⭐⭐

**Fecha de completación:** _______________
