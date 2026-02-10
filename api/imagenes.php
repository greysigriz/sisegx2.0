<?php
/**
 * API para manejo de imágenes del sistema
 * Soporta upload, delete y listado de imágenes
 *
 * Endpoints:
 * - POST /imagenes.php - Upload de imágenes
 * - GET /imagenes.php?entidad_tipo=X&entidad_id=Y - Listar imágenes
 * - DELETE /imagenes.php - Eliminar imagen
 */

require_once '../config/database.php';

// Inicializar conexión a base de datos
$database = new Database();
$pdo = $database->getConnection();

// Configuración de CORS mejorada para withCredentials
$origin = $_SERVER['HTTP_ORIGIN'] ?? 'http://localhost:5173';
$allowed_origins = [
    'http://localhost:5173',
    'http://localhost:3000',
    'http://localhost',
    'http://127.0.0.1:5173'
];

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header('Access-Control-Allow-Origin: http://localhost:5173');
}

header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, x-auth-token, X-Auth-Token');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configuración de imágenes
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('MAX_IMAGES_PER_ENTITY', 3);
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/bmp']);
define('UPLOADS_BASE_PATH', $_SERVER['DOCUMENT_ROOT'] . '/SISEE/uploads');

// Generar URL base absoluta para las imágenes
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
define('UPLOADS_BASE_URL', "$protocol://$host/SISEE/uploads");

// Crear directorio uploads si no existe
if (!file_exists(UPLOADS_BASE_PATH)) {
    mkdir(UPLOADS_BASE_PATH, 0755, true);
}

/**
 * Obtener información del usuario logueado
 */
function obtenerUsuarioLogueado() {
    session_start();

    // Verificar sesión PHP
    if (isset($_SESSION['user_id'])) {
        return ['id' => $_SESSION['user_id'], 'nombre' => $_SESSION['user_name'] ?? 'Usuario'];
    }

    // Verificar header Authorization
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        // Aquí puedes implementar verificación de JWT si usas tokens
        // Por ahora, asumir que es válido
        return ['id' => 1, 'nombre' => 'Usuario Sistema'];
    }

    return null;
}

/**
 * Generar nombre único para archivo
 */
function generarNombreArchivo($originalName, $entidadTipo, $entidadId) {
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $timestamp = date('YmdHis');
    $random = substr(md5(uniqid()), 0, 8);
    return "{$entidadTipo}_{$entidadId}_{$timestamp}_{$random}.{$extension}";
}

/**
 * Crear directorio por fecha
 */
function crearDirectorioFecha($entidadTipo) {
    $year = date('Y');
    $month = date('m');
    $relativePath = "{$entidadTipo}/{$year}/{$month}";
    $fullPath = UPLOADS_BASE_PATH . "/{$relativePath}";

    if (!file_exists($fullPath)) {
        mkdir($fullPath, 0755, true);
    }

    return $relativePath;
}

/**
 * Obtener dimensiones de imagen
 */
function obtenerDimensiones($filePath) {
    $imageInfo = getimagesize($filePath);
    return $imageInfo ? ['width' => $imageInfo[0], 'height' => $imageInfo[1]] : null;
}

/**
 * Validar límite de imágenes por entidad
 */
function validarLimiteImagenes($pdo, $entidadTipo, $entidadId) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM imagenes
        WHERE entidad_tipo = ? AND entidad_id = ? AND activa = 1
    ");
    $stmt->execute([$entidadTipo, $entidadId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return ($result['total'] < MAX_IMAGES_PER_ENTITY);
}

/**
 * UPLOAD DE IMÁGENES
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        global $pdo;

        $usuario = obtenerUsuarioLogueado();
        if (!$usuario) {
            throw new Exception('Usuario no autenticado');
        }

        // Validar parámetros requeridos
        $entidadTipo = $_POST['entidad_tipo'] ?? '';
        $entidadId = intval($_POST['entidad_id'] ?? 0);

        if (!in_array($entidadTipo, ['peticion', 'historial_cambio'])) {
            throw new Exception('Tipo de entidad no válido');
        }

        if ($entidadId <= 0) {
            throw new Exception('ID de entidad no válido');
        }

        // Verificar que hay archivos
        if (!isset($_FILES['imagenes']) || empty($_FILES['imagenes']['name'][0])) {
            throw new Exception('No se enviaron archivos');
        }

        // Validar límite de imágenes
        if (!validarLimiteImagenes($pdo, $entidadTipo, $entidadId)) {
            throw new Exception('Ya se alcanzó el límite máximo de ' . MAX_IMAGES_PER_ENTITY . ' imágenes');
        }

        $imagenesSubidas = [];
        $errores = [];

        // Procesar cada archivo
        $files = $_FILES['imagenes'];
        $fileCount = count($files['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            try {
                // Verificar límite nuevamente por cada archivo
                if (!validarLimiteImagenes($pdo, $entidadTipo, $entidadId)) {
                    $errores[] = "Límite de imágenes alcanzado";
                    break;
                }

                $fileName = $files['name'][$i];
                $fileSize = $files['size'][$i];
                $fileTmpName = $files['tmp_name'][$i];
                $fileError = $files['error'][$i];

                // Validar errores de upload
                if ($fileError !== UPLOAD_ERR_OK) {
                    $errores[] = "Error al subir {$fileName}";
                    continue;
                }

                // Validar tamaño
                if ($fileSize > MAX_FILE_SIZE) {
                    $errores[] = "{$fileName}: El archivo excede el tamaño máximo de " . (MAX_FILE_SIZE/1024/1024) . "MB";
                    continue;
                }

                // Validar tipo MIME
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $fileTmpName);
                finfo_close($finfo);

                if (!in_array($mimeType, ALLOWED_TYPES)) {
                    $errores[] = "{$fileName}: Formato no permitido. Use JPG, PNG, WebP, GIF o BMP";
                    continue;
                }

                // Crear directorio y nombres
                $relativePath = crearDirectorioFecha($entidadTipo);
                $storageFileName = generarNombreArchivo($fileName, $entidadTipo, $entidadId);
                $fullPath = UPLOADS_BASE_PATH . "/{$relativePath}/{$storageFileName}";
                $urlAcceso = UPLOADS_BASE_URL . "/{$relativePath}/{$storageFileName}";

                // Mover archivo
                if (!move_uploaded_file($fileTmpName, $fullPath)) {
                    $errores[] = "Error al guardar {$fileName}";
                    continue;
                }

                // Obtener dimensiones
                $dimensiones = obtenerDimensiones($fullPath);

                // Obtener próximo orden
                $stmt = $pdo->prepare("
                    SELECT COALESCE(MAX(orden), 0) + 1 as siguiente_orden
                    FROM imagenes
                    WHERE entidad_tipo = ? AND entidad_id = ? AND activa = 1
                ");
                $stmt->execute([$entidadTipo, $entidadId]);
                $orden = $stmt->fetch(PDO::FETCH_ASSOC)['siguiente_orden'];

                // Guardar en base de datos
                $stmt = $pdo->prepare("
                    INSERT INTO imagenes (
                        entidad_tipo, entidad_id, filename_original, filename_storage,
                        path_relativo, url_acceso, mime_type, file_size, width, height,
                        orden, usuario_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $entidadTipo, $entidadId, $fileName, $storageFileName,
                    $relativePath, $urlAcceso, $mimeType, $fileSize,
                    $dimensiones['width'] ?? null, $dimensiones['height'] ?? null,
                    $orden, $usuario['id']
                ]);

                $imagenesSubidas[] = [
                    'id' => $pdo->lastInsertId(),
                    'filename_original' => $fileName,
                    'url_acceso' => $urlAcceso,
                    'file_size' => $fileSize,
                    'mime_type' => $mimeType,
                    'width' => $dimensiones['width'] ?? null,
                    'height' => $dimensiones['height'] ?? null,
                    'orden' => $orden
                ];

            } catch (Exception $e) {
                $errores[] = "Error procesando {$fileName}: " . $e->getMessage();
            }
        }

        echo json_encode([
            'success' => !empty($imagenesSubidas),
            'message' => count($imagenesSubidas) . ' imagen(es) subida(s) correctamente',
            'imagenes' => $imagenesSubidas,
            'errores' => $errores
        ]);

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * LISTAR IMÁGENES
 */
else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        global $pdo;

        $entidadTipo = $_GET['entidad_tipo'] ?? '';
        $entidadId = intval($_GET['entidad_id'] ?? 0);

        if (!in_array($entidadTipo, ['peticion', 'historial_cambio'])) {
            throw new Exception('Tipo de entidad no válido');
        }

        if ($entidadId <= 0) {
            throw new Exception('ID de entidad no válido');
        }

        $stmt = $pdo->prepare("
            SELECT
                i.*,
                u.Nombre as usuario_nombre
            FROM imagenes i
            LEFT JOIN Usuario u ON i.usuario_id = u.Id
            WHERE i.entidad_tipo = ? AND i.entidad_id = ? AND i.activa = 1
            ORDER BY i.orden ASC, i.fecha_subida ASC
        ");

        $stmt->execute([$entidadTipo, $entidadId]);
        $imagenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Convertir URLs relativas a absolutas
        foreach ($imagenes as &$imagen) {
            if (isset($imagen['url_acceso']) && strpos($imagen['url_acceso'], 'http') !== 0) {
                $imagen['url_acceso'] = UPLOADS_BASE_URL . str_replace('/SISEE/uploads', '', $imagen['url_acceso']);
            }
        }

        echo json_encode([
            'success' => true,
            'imagenes' => $imagenes,
            'total' => count($imagenes)
        ]);

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * ELIMINAR IMAGEN
 */
else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        global $pdo;

        $input = json_decode(file_get_contents('php://input'), true);
        $imagenId = intval($input['imagen_id'] ?? 0);

        if ($imagenId <= 0) {
            throw new Exception('ID de imagen no válido');
        }

        // Obtener información de la imagen
        $stmt = $pdo->prepare("SELECT * FROM imagenes WHERE id = ? AND activa = 1");
        $stmt->execute([$imagenId]);
        $imagen = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$imagen) {
            throw new Exception('Imagen no encontrada');
        }

        // Eliminar archivo físico
        $filePath = UPLOADS_BASE_PATH . "/{$imagen['path_relativo']}/{$imagen['filename_storage']}";
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Eliminar de base de datos (borrado lógico)
        $stmt = $pdo->prepare("UPDATE imagenes SET activa = 0 WHERE id = ?");
        $stmt->execute([$imagenId]);

        echo json_encode([
            'success' => true,
            'message' => 'Imagen eliminada correctamente'
        ]);

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}
?>