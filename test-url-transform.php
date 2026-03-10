<?php
/**
 * Script de prueba para transformación de URLs de imágenes
 */

// Detectar entorno
$isLocalDev = (strpos($_SERVER['DOCUMENT_ROOT'], 'xampp') !== false || 
               strpos($_SERVER['DOCUMENT_ROOT'], 'htdocs') !== false);
$baseFolder = $isLocalDev ? '/SISEE' : '';

// Generar URL base
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$UPLOADS_BASE_URL = "$protocol://$host$baseFolder/uploads";

echo "=== TEST DE TRANSFORMACIÓN DE URLs ===\n\n";
echo "Entorno: " . ($isLocalDev ? "DESARROLLO" : "PRODUCCIÓN") . "\n";
echo "UPLOADS_BASE_URL: $UPLOADS_BASE_URL\n\n";

// URLs de prueba (como están en la base de datos)
$testUrls = [
    'http://localhost/SISEE/uploads/historial_cambio/2026/02/historial_cambio_13_20260213210834_eac00a85.png',
    '/SISEE/uploads/historial_cambio/2026/02/historial_cambio_13_20260213210834_eac00a85.png',
    '/uploads/historial_cambio/2026/02/historial_cambio_13_20260213210834_eac00a85.png',
    'historial_cambio/2026/02/historial_cambio_13_20260213210834_eac00a85.png',
];

foreach ($testUrls as $url) {
    echo "URL ORIGINAL: $url\n";
    
    $urlTransformada = $url;
    
    // Si no es absoluta, aplicar transformación
    if (strpos($urlTransformada, 'http') !== 0) {
        // Eliminar /SISEE/uploads o /uploads del inicio
        $rutaRelativa = preg_replace('#^(/SISEE)?/uploads/#', '', $urlTransformada);
        
        // Construir URL absoluta correcta
        $urlTransformada = $UPLOADS_BASE_URL . '/' . $rutaRelativa;
    } else {
        // Si es absoluta pero tiene ruta incorrecta, corregir
        $rutaRelativa = preg_replace('#^https?://[^/]+(/SISEE)?/uploads/#', '', $urlTransformada);
        $urlTransformada = $UPLOADS_BASE_URL . '/' . $rutaRelativa;
    }
    
    echo "URL TRANSFORMADA: $urlTransformada\n\n";
}

echo "=== FIN DEL TEST ===\n";
