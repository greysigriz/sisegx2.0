<?php
// api/login.php
// Configuración inicial para debugging 
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// Crear directorio de logs si no existe
if (!is_dir(__DIR__ . '/logs')) {
    @mkdir(__DIR__ . '/logs', 0755, true);
}

// Función para logging personalizado
function debugLog($message, $data = null) {
    $logFile = __DIR__ . '/logs/login_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    
    if ($data !== null) {
        $logMessage .= " - Data: " . print_r($data, true);
    }
    
    $logMessage .= PHP_EOL;
    @file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

// Registrar handler de errores fatales
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        debugLog("FATAL ERROR", $error);
        
        // Solo enviar respuesta si no se ha enviado ya
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error fatal del servidor',
                'debug' => [
                    'error' => $error['message'],
                    'file' => $error['file'],
                    'line' => $error['line']
                ]
            ]);
        }
    }
});

// CORS debe ir PRIMERO, antes de cualquier output
$corsFile = __DIR__ . '/cors.php';
if (!file_exists($corsFile)) {
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de configuración: archivo cors.php no encontrado'
    ]);
    exit;
}
require_once $corsFile;

// Headers después de CORS
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Solo setear Content-Type si no es OPTIONS (ya manejado en CORS)
if ($_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
    header('Content-Type: application/json; charset=UTF-8');
}

debugLog("=== INICIO LOGIN REQUEST ===");
debugLog("Request Method: " . $_SERVER['REQUEST_METHOD']);
debugLog("PHP Version: " . phpversion());

// Verificar método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    debugLog("Método incorrecto: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

try {
    // Leer datos JSON del body
    $json_input = file_get_contents("php://input");
    debugLog("JSON Input raw", $json_input);
    
    if (empty($json_input)) {
        debugLog("JSON input vacío");
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No se recibieron datos JSON'
        ]);
        exit;
    }

    $data = json_decode($json_input, true);
    debugLog("JSON decoded", $data);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        debugLog("Error JSON decode: " . json_last_error_msg());
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'JSON inválido: ' . json_last_error_msg()
        ]);
        exit;
    }

    if (!isset($data['usuario']) || !isset($data['password'])) {
        debugLog("Faltan campos requeridos", $data);
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Faltan datos de usuario o contraseña'
        ]);
        exit;
    }

    // Verificar extensiones PHP necesarias
    if (!extension_loaded('pdo')) {
        debugLog("ERROR: Extensión PDO no cargada");
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error de configuración: extensión PDO no disponible'
        ]);
        exit;
    }
    
    if (!extension_loaded('pdo_mysql')) {
        debugLog("ERROR: Extensión PDO_MySQL no cargada");
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error de configuración: extensión PDO_MySQL no disponible'
        ]);
        exit;
    }

    // Verificar archivo de base de datos
    $dbConfigPath = __DIR__ . '/../config/database.php';
    debugLog("Buscando database.php en: " . $dbConfigPath);
    
    if (!file_exists($dbConfigPath)) {
        debugLog("ERROR: database.php no encontrado");
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error de configuración del servidor',
            'debug' => 'database.php no encontrado en: ' . $dbConfigPath
        ]);
        exit;
    }

    debugLog("Cargando database.php");
    require_once $dbConfigPath;
    
    if (!class_exists('Database')) {
        debugLog("ERROR: Clase Database no existe después de require");
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error de configuración: clase Database no definida'
        ]);
        exit;
    }

    debugLog("Creando instancia de Database");
    $database = new Database();
    
    debugLog("Obteniendo conexión");
    
    // ✅ CAMBIO: Manejar la excepción que ahora lanza getConnection()
    try {
        $db = $database->getConnection();
    } catch (Exception $dbException) {
        debugLog("ERROR de conexión a BD: " . $dbException->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error de conexión a la base de datos: ' . $dbException->getMessage()
        ]);
        exit;
    }
    
    if (!$db) {
        debugLog("ERROR: getConnection() devolvió null");
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error de conexión a la base de datos.'
        ]);
        exit;
    }
    
    debugLog("Conexión a base de datos exitosa");

    // Clase Login
    class Login {
        private $conn;
        private $table_name = 'Usuario';

        public function __construct($db) {
            $this->conn = $db;
        }

        public function login($usuario, $password) {
            debugLog("Iniciando proceso de login para usuario: " . $usuario);
            
            // Primero verificar que la tabla existe
            try {
                $checkTable = $this->conn->query("SHOW TABLES LIKE 'Usuario'");
                if ($checkTable->rowCount() == 0) {
                    debugLog("ERROR: Tabla Usuario no existe");
                    return ['success' => false, 'message' => 'Error de configuración: tabla Usuario no existe'];
                }
            } catch (Exception $e) {
                debugLog("Error verificando tabla: " . $e->getMessage());
            }
            
            // ✅ Query simplificado - solo tabla Usuario primero
            $query = "SELECT Id, Usuario, Nombre, ApellidoP, ApellidoM, Puesto, 
                             Estatus, IdDivisionAdm, IdUnidad, IdRolSistema, Password
                      FROM " . $this->table_name . "
                      WHERE Usuario = :usuario";

            try {
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':usuario', $usuario);
                $stmt->execute();

                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                debugLog("Query ejecutado, usuario encontrado: " . ($user ? 'SI' : 'NO'));

                if ($user) {
                    debugLog("Usuario encontrado en BD", [
                        'Id' => $user['Id'],
                        'Usuario' => $user['Usuario'],
                        'Estatus' => $user['Estatus']
                    ]);

                    $dbPassword = $user['Password'];
                    $valid = false;

                    // Verificar contraseña
                    if (password_verify($password, $dbPassword)) {
                        $valid = true;
                        debugLog("Contraseña verificada con hash");
                    } elseif ($password === $dbPassword) {
                        $valid = true;
                        debugLog("Contraseña en texto plano, actualizando hash");

                        // Actualizar a hash
                        $newHash = password_hash($password, PASSWORD_DEFAULT);
                        $update = "UPDATE Usuario SET Password = :newHash WHERE Id = :id";
                        $updateStmt = $this->conn->prepare($update);
                        $updateStmt->bindParam(':newHash', $newHash);
                        $updateStmt->bindParam(':id', $user['Id']);
                        $updateStmt->execute();
                    }

                    if ($valid) {
                        if ($user['Estatus'] !== 'ACTIVO') {
                            debugLog("Usuario inactivo: " . $user['Estatus']);
                            return ['success' => false, 'message' => 'Usuario inactivo'];
                        }

                        debugLog("Login exitoso, obteniendo datos adicionales");
                        
                        // ✅ Obtener todos los roles del usuario desde UsuarioRol
                        $roles = $this->getUserRoles($user['Id']);
                        $rolesIds = array_column($roles, 'Id');
                        $rolesNombres = array_column($roles, 'Nombre');

                        // ✅ Obtener todos los permisos del usuario desde RolPermiso + Permiso
                        $permisos = $this->getUserPermisos($user['Id']);
                        
                        // ✅ Obtener datos del rol principal (compatibilidad)
                        $rolData = $this->getRolData($user['IdRolSistema']);
                        
                        // ✅ Obtener datos de división
                        $divisionData = $this->getDivisionData($user['IdDivisionAdm']);
                        
                        $unidades = $this->getUserUnidades($user['Id']);
                        unset($user['Password']);

                        // Agregar nuevos campos al usuario
                        $user['Roles'] = $roles;
                        $user['RolesIds'] = $rolesIds;
                        $user['RolesNombres'] = $rolesNombres;
                        $user['Permisos'] = $permisos;

                        $userData = [
                            'usuario' => $user,
                            'rol' => $rolData,
                            'division' => $divisionData,
                            'unidades' => $unidades,
                            'permisos' => $permisos
                        ];

                        debugLog("Datos de usuario preparados - " . count($permisos) . " permisos cargados");
                        return ['success' => true, 'user' => $userData];
                    } else {
                        debugLog("Contraseña incorrecta");
                    }
                } else {
                    debugLog("Usuario no encontrado en BD: " . $usuario);
                }

                return ['success' => false, 'message' => 'Credenciales inválidas'];
                
            } catch (Exception $e) {
                debugLog("Error en query de login: " . $e->getMessage());
                throw $e;
            }
        }

        // ✅ Nuevo método para obtener datos del rol
        private function getRolData($rolId) {
            if (!$rolId) {
                return ['id' => null, 'nombre' => 'Sin rol', 'descripcion' => ''];
            }
            
            try {
                $stmt = $this->conn->query("DESCRIBE RolSistema");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                debugLog("Columnas de RolSistema: ", $columns);
                
                $stmt = $this->conn->prepare("SELECT * FROM RolSistema WHERE Id = :id");
                $stmt->bindParam(':id', $rolId);
                $stmt->execute();
                $rol = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($rol) {
                    return [
                        'id' => $rol['Id'] ?? $rolId,
                        'nombre' => $rol['Nombre'] ?? $rol['nombre'] ?? 'Rol ' . $rolId,
                        'descripcion' => $rol['Descripcion'] ?? $rol['descripcion'] ?? ''
                    ];
                }
            } catch (Exception $e) {
                debugLog("Error obteniendo rol: " . $e->getMessage());
            }
            
            return ['id' => $rolId, 'nombre' => 'Rol ' . $rolId, 'descripcion' => ''];
        }

        // ✅ Método actualizado para obtener datos de división con columnas correctas
        private function getDivisionData($divisionId) {
            if (!$divisionId) {
                return ['id' => null, 'nombre' => 'Sin división', 'pais' => '', 'estado' => '', 'municipio' => '', 'codigoPostal' => '', 'cabecera' => ''];
            }
            
            try {
                $stmt = $this->conn->prepare("SELECT * FROM DivisionAdministrativa WHERE Id = :id");
                $stmt->bindParam(':id', $divisionId);
                $stmt->execute();
                $division = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($division) {
                    debugLog("División encontrada: ", $division);
                    return [
                        'id' => $division['Id'],
                        'nombre' => $division['Municipio'] ?? 'División ' . $divisionId,
                        'municipio' => $division['Municipio'] ?? '',
                        'pais' => $division['Pais'] ?? 'México',
                        'estado' => $division['Estado'] ?? 'Yucatán',
                        'codigoPostal' => $division['CodigoPostal'] ?? '',
                        'cabecera' => $division['Cabecera'] ?? ''
                    ];
                }
            } catch (Exception $e) {
                debugLog("Error obteniendo división: " . $e->getMessage());
            }
            
            return ['id' => $divisionId, 'nombre' => 'División ' . $divisionId, 'pais' => '', 'estado' => '', 'municipio' => '', 'codigoPostal' => '', 'cabecera' => ''];
        }

        private function getUserUnidades($userId) {
            $query = "SELECT u.id as unidad_id, u.clave, u.nombre_unidad, u.estatus, 
                             u.nivel, u.tipo_cuenta, u.periodo, u.abreviatura, u.siglas
                      FROM usuario_unidad uu
                      JOIN unidades u ON uu.unidad_id = u.id
                      WHERE uu.usuario_id = :userId";

            try {
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':userId', $userId);
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                debugLog("Error obteniendo unidades: " . $e->getMessage());
                return [];
            }
        }

        // ✅ Obtener todos los roles del usuario desde UsuarioRol
        private function getUserRoles($userId) {
            try {
                $query = "SELECT r.Id, r.Nombre, r.Descripcion
                          FROM UsuarioRol ur
                          JOIN RolSistema r ON ur.IdRolSistema = r.Id
                          WHERE ur.IdUsuario = :userId
                          ORDER BY r.Nombre";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':userId', $userId);
                $stmt->execute();
                
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                debugLog("Error obteniendo roles: " . $e->getMessage());
                return [];
            }
        }

        // ✅ Obtener todos los permisos del usuario desde RolPermiso + Permiso
        private function getUserPermisos($userId) {
            try {
                $query = "SELECT DISTINCT p.Codigo
                          FROM UsuarioRol ur
                          JOIN RolPermiso rp ON ur.IdRolSistema = rp.IdRolSistema
                          JOIN Permiso p ON rp.IdPermiso = p.Id
                          WHERE ur.IdUsuario = :userId";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':userId', $userId);
                $stmt->execute();
                
                $permisos = [];
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $permisos[] = $row['Codigo'];
                }
                
                debugLog("Permisos cargados para usuario $userId: " . count($permisos) . " permisos");
                return $permisos;
            } catch (Exception $e) {
                debugLog("Error obteniendo permisos: " . $e->getMessage());
                return ['ver_dashboard']; // Fallback mínimo
            }
        }
    }

    // Procesar login
    $login = new Login($db);
    $result = $login->login($data['usuario'], $data['password']);

    if ($result['success']) {
        debugLog("Login exitoso, iniciando sesión");
        
        // Configurar sesión
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.gc_maxlifetime', 8 * 60 * 60);
            ini_set('session.cookie_lifetime', 8 * 60 * 60);
            ini_set('session.cookie_path', '/');
            ini_set('session.cookie_domain', ''); // Vacío para funcionar con localhost e IP
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.cookie_httponly', '1');
            session_start();
            session_regenerate_id(true);
        }

        $_SESSION['user_id'] = $result['user']['usuario']['Id'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['user_data'] = [
            'usuario' => $result['user']['usuario'],
            'rol' => $result['user']['rol'],
            'permisos' => $result['user']['permisos'],
            'unidades' => $result['user']['unidades']
        ];

        debugLog("Sesión configurada, enviando respuesta exitosa");
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'user' => $result['user'],
            'session_id' => session_id()
        ]);
    } else {
        debugLog("Login fallido: " . $result['message']);
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }

} catch(Exception $e) {
    debugLog("EXCEPCIÓN CAPTURADA: " . $e->getMessage());
    debugLog("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage(),
        'debug' => [
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ]);
}

debugLog("=== FIN LOGIN REQUEST ===");
?>