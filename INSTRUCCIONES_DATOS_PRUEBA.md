# 📊 Insertar Datos de Prueba para el Gráfico de Tendencia Mensual

## 🎯 Objetivo
Insertar datos de prueba en la tabla `peticion_departamento` para visualizar el gráfico de "Tendencia Mensual" en el dashboard.

## 📋 Requisitos
- XAMPP corriendo (Apache y MySQL)
- Base de datos configurada correctamente
- Tabla `peticion_departamento` debe existir

## 🚀 Instrucciones

### Opción 1: Ejecutar desde el navegador

1. **Abrir el navegador** y visitar:
   ```
   http://localhost/SISEE/api/generar-datos-grafico.php
   ```

2. **Esperar** a que el script termine (puede tomar unos segundos)

3. **Verificar** que se muestre el mensaje de éxito y el resumen de datos

4. **Recargar el dashboard** para ver el gráfico con datos

### Opción 2: Ejecutar desde terminal/línea de comandos

1. **Abrir Terminal** (Mac/Linux) o **CMD** (Windows)

2. **Navegar** a la carpeta del proyecto:
   ```bash
   cd /Applications/XAMPP/xamppfiles/htdocs/SISEE
   ```

3. **Ejecutar** el script:
   ```bash
   php api/generar-datos-grafico.php
   ```

4. **Ver el resultado** en la consola

5. **Recargar el dashboard** en el navegador

## 📊 ¿Qué hace el script?

El script `generar-datos-grafico.php`:

✅ Detecta automáticamente la estructura de tu tabla
✅ Genera entre 50-100 registros aleatorios
✅ Distribuye los datos en los últimos 30 días
✅ Incluye todos los estados del gráfico:
   - Esperando recepción
   - Aceptado en proceso
   - Devuelto a seguimiento
   - Rechazado
   - Completado
✅ Muestra un resumen de los datos insertados

## 🔍 Verificar que funciona

Después de ejecutar el script:

1. Ve a tu dashboard
2. Busca el componente "Tendencia Mensual"
3. Deberías ver líneas de colores mostrando la evolución de los estados
4. Prueba cambiar el selector de rango (Última semana, Último mes, etc.)

## ⚠️ Solución de problemas

### Error: "La tabla no existe"
- Verifica que la tabla `peticion_departamento` está creada en tu base de datos
- Revisa phpmyadmin: http://localhost/phpmyadmin

### Error: "Conexión rechazada"
- Asegúrate de que XAMPP está corriendo
- Verifica que MySQL está iniciado en el panel de XAMPP

### Error: "Columna no encontrada"
- El script se adaptará automáticamente a tu estructura
- Si persiste, revisa la estructura de tu tabla en phpmyadmin

### El gráfico sigue vacío
- Abre la consola del navegador (F12)
- Busca errores en la pestaña Console
- Verifica que la API responde: http://localhost/SISEE/api/dashboard-estados.php?dias=7

## 🗑️ Limpiar datos de prueba

Si quieres eliminar los datos de prueba:

```sql
DELETE FROM peticion_departamento 
WHERE observaciones = 'Registro de prueba generado automáticamente';
```

O si quieres borrar TODO (⚠️ cuidado):

```sql
TRUNCATE TABLE peticion_departamento;
```

## 📝 Notas

- Los datos son completamente ficticios
- Se generan fechas de los últimos 30 días
- Cada día tiene entre 1-6 registros aleatorios
- Los estados se distribuyen aleatoriamente

## 💡 Próximos pasos

Una vez que veas el gráfico funcionando:
- Puedes ajustar los colores en `AreaChartt.vue`
- Modificar el rango de fechas en `dashboard-estados.php`
- Conectar con datos reales de tu sistema
