<?php
/**
 * Endpoint para forzar la descarga de imágenes
 * Sirve archivos con headers que fuerzan la descarga en lugar de visualización
 */

// Manejo de CORS para preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, x-auth-token, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400'); // Cache preflight por 24 horas
    http_response_code(200);
    exit();
}

try {
    // Obtener parámetros
    $archivo = isset($_GET['archivo']) ? $_GET['archivo'] : '';
    $nombre = isset($_GET['nombre']) ? $_GET['nombre'] : '';

    // Log para debugging
    error_log("📥 Descarga solicitada - Archivo: $archivo, Nombre: $nombre");

    if (empty($archivo)) {
        throw new Exception('No se especificó el archivo');
    }

    // Construir ruta completa del archivo
    // Remover cualquier protocolo o dominio si existe
    $archivo = preg_replace('#^https?://[^/]+#', '', $archivo);
    
    // Remover el prefijo /SISEE/ si existe
    $archivo = preg_replace('#^/SISEE/#', '', $archivo);
    
    // Remover api/ si está al inicio
    $archivo = preg_replace('#^/?api/#', '', $archivo);
    
    // Remover barra inicial si existe
    $archivo = ltrim($archivo, '/');
    
    error_log("📁 Ruta procesada: $archivo");
    
    // Construir ruta absoluta
    $rutaBase = dirname(__DIR__);
    $rutaArchivo = $rutaBase . '/' . $archivo;

    error_log("🗂️ Ruta completa: $rutaArchivo");
    error_log("📍 Ruta base: $rutaBase");

    // Seguridad: verificar que el archivo esté dentro de la carpeta uploads
    $uploadsPath = realpath($rutaBase . '/uploads');
    $archivoReal = realpath($rutaArchivo);

    error_log("✅ Uploads path: $uploadsPath");
    error_log("✅ Archivo real: $archivoReal");

    if ($archivoReal === false) {
        error_log("❌ Error: El archivo no existe - $rutaArchivo");
        throw new Exception('El archivo no existe en la ruta especificada');
    }

    if (strpos($archivoReal, $uploadsPath) !== 0) {
        error_log("❌ Error: Acceso denegado - archivo fuera de uploads");
        throw new Exception('Acceso denegado: archivo fuera de la carpeta permitida');
    }

    if (!file_exists($archivoReal)) {
        throw new Exception('El archivo no existe');
    }

    if (!is_readable($archivoReal)) {
        throw new Exception('No se puede leer el archivo');
    }

    // Obtener información del archivo
    $nombreArchivo = !empty($nombre) ? $nombre : basename($archivoReal);
    $tipoMime = mime_content_type($archivoReal);
    $tamano = filesize($archivoReal);

    error_log("✅ Archivo encontrado - Tipo: $tipoMime, Tamaño: $tamano bytes, Nombre: $nombreArchivo");

    // Limpiar cualquier salida previa
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Establecer headers CORS
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET');
    header('Access-Control-Expose-Headers: Content-Disposition, Content-Length');
    
    // Establecer headers para forzar descarga
    header('Content-Type: ' . $tipoMime);
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
    header('Content-Length: ' . $tamano);
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: public');
    header('Expires: 0');
    
    // Deshabilitar compresión
    header('Content-Encoding: none');

    // Leer y enviar el archivo en chunks para archivos grandes
    $handle = fopen($archivoReal, 'rb');
    if ($handle === false) {
        throw new Exception('No se puede abrir el archivo');
    }

    while (!feof($handle)) {
        echo fread($handle, 8192);
        flush();
    }

    fclose($handle);
    exit();

} catch (Exception $e) {
    // Limpiar buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Log del error
    error_log("❌ Error en descarga de imagen: " . $e->getMessage());
    
    // Enviar error como JSON
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit();
}
