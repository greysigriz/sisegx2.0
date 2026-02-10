# Sistema de Imágenes - SISEE

## Estructura de Carpetas

```
uploads/
├── peticiones/          # Imágenes de peticiones ciudadanas
│   ├── 2026/
│   │   ├── 01/
│   │   └── 02/
│   └── ...
└── historial/          # Imágenes de cambios de estado
    ├── 2026/
    │   ├── 01/
    │   └── 02/
    └── ...
```

## Configuración de Seguridad

### Restricciones de Access (.htaccess)

Crear un archivo `.htaccess` en la carpeta `uploads` con el siguiente contenido para mejorar la seguridad:

```apache
# Denegar ejecución de scripts
Options -ExecCGI
AddHandler cgi-script .php .pl .py .jsp .asp .sh .cgi

# Solo permitir tipos de archivo de imagen
<FilesMatch "\.(jpg|jpeg|png|gif|webp|bmp)$">
    Order allow,deny
    Allow from all
</FilesMatch>

# Denegar todo lo demás
<FilesMatch "^.*$">
    Order deny,allow
    Deny from all
</FilesMatch>

# Denegar acceso a archivos de configuración
<Files ~ "^\.">
    Order allow,deny
    Deny from all
</Files>

# Headers de seguridad
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
```

### Permisos de Carpeta

En sistemas Windows con XAMPP:
- Carpeta `uploads`: Permisos de lectura/escritura para el usuario de Apache
- Subcarpetas se crean automáticamente con permisos heredados

En sistemas Linux:
```bash
chmod 755 uploads/
chmod 755 uploads/peticiones/
chmod 755 uploads/historial/
```

## Uso del Sistema

### 1. En Peticiones (PetitionPage.vue)
- Los ciudadanos pueden subir hasta 3 imágenes al crear una petición
- Las imágenes se guardan en `uploads/peticiones/YYYY/MM/`
- Formatos permitidos: JPG, PNG, WebP, GIF, BMP
- Tamaño máximo: 10MB por imagen

### 2. En Cambios de Estado (TablaDepartamento.vue)
- Los departamentos pueden subir hasta 3 imágenes al cambiar el estado
- Las imágenes se guardan en `uploads/historial/YYYY/MM/`
- Mismas restricciones de formato y tamaño

### 3. Nomenclatura de Archivos
```
{entidad}_{entidad_id}_{timestamp}_{random}.{extension}

Ejemplos:
- peticion_123_20260205143020_a1b2c3d4.jpg
- historial_456_20260205143025_e5f6g7h8.png
```

## API Endpoints

### POST /api/imagenes.php
Subir imágenes
```
FormData:
- entidad_tipo: 'peticion' | 'historial_cambio'
- entidad_id: ID numérico
- imagenes[]: Array de archivos
```

### GET /api/imagenes.php
Listar imágenes
```
Query params:
- entidad_tipo: 'peticion' | 'historial_cambio'
- entidad_id: ID numérico
```

### DELETE /api/imagenes.php
Eliminar imagen
```
JSON Body:
- imagen_id: ID numérico de la imagen
```

## Componentes Vue

### ImageUpload.vue
- Componente reutilizable para subir imágenes
- Drag & drop support
- Vista previa
- Validaciones automáticas
- Progress tracking

### ImageGallery.vue
- Componente para mostrar galerías de imágenes
- Múltiples layouts (grid, list, carousel)
- Modal de vista ampliada
- Funciones de descarga y eliminación

## Base de Datos

### Tabla: imagenes
```sql
CREATE TABLE `imagenes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entidad_tipo` enum('peticion', 'historial_cambio') NOT NULL,
  `entidad_id` int(11) NOT NULL,
  `filename_original` varchar(255) NOT NULL,
  `filename_storage` varchar(255) NOT NULL,
  `path_relativo` varchar(500) NOT NULL,
  `url_acceso` varchar(500) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size` int(11) NOT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `orden` tinyint(4) DEFAULT 1,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) DEFAULT NULL,
  `activa` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  -- ... otros índices y constraints
);
```

## Mantenimiento

### Limpieza Automática
- Implementar cronjob para eliminar imágenes antiguas no referenciadas
- Compresión automática de imágenes grandes
- Respaldos periódicos de la carpeta uploads

### Monitoreo
- Espacio en disco utilizado
- Número de imágenes por mes
- Detección de uploads maliciosos

## Seguridad

### Validaciones Implementadas
- ✅ Tipo MIME verificado
- ✅ Extensión de archivo validada
- ✅ Tamaño máximo limitado
- ✅ Nombres de archivo únicos
- ✅ Estructura de directorios organizada
- ✅ Borrado lógico (soft delete)
- ✅ Límite de imágenes por entidad

### Recomendaciones Adicionales
- [ ] Escaneo antivirus de archivos subidos
- [ ] Rate limiting en uploads
- [ ] Watermarks automáticos
- [ ] CDN para servir imágenes
- [ ] Backup automático a cloud storage