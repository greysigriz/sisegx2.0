<?php
// api/login.php
// Configuración inicial para debugging 
error_reporting(E_ALL);
ini_set('display_errors', 0); // Cambiar a 1 solo para debugging local
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// Crear directorio de logs si no existe
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
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
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

// CORS debe ir PRIMERO, antes de cualquier output
require_once __DIR__ . '/cors.php';

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
debugLog("Headers", getallheaders());

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

    // Verificar conexión a base de datos
    debugLog("Intentando conectar a base de datos");
    require_once __DIR__ . '/../config/database.php';
    
    if (!file_exists(__DIR__ . '/../config/database.php')) {
        debugLog("ERROR: No se encuentra database.php");
        throw new Exception("Archivo de configuración de base de datos no encontrado");
    }

    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        debugLog("ERROR: No se pudo conectar a la base de datos");
        throw new Exception("Error de conexión a la base de datos");
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
            
            $query = "SELECT u.Id, u.Usuario, u.Nombre, u.ApellidoP, u.ApellidoM, u.Puesto, 
                             u.Estatus, u.IdDivisionAdm, u.IdUnidad, u.IdRolSistema, u.Password,
                             r.Nombre as RolNombre, r.Descripcion as RolDescripcion,
                             d.Nombre as DivisionNombre, d.Pais, d.Region, d.Ciudad 
                      FROM " . $this->table_name . " u
                      LEFT JOIN RolSistema r ON u.IdRolSistema = r.Id
                      LEFT JOIN DivisionAdministrativa d ON u.IdDivisionAdm = d.Id
                      WHERE u.Usuario = :usuario";

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
                        
                        $unidades = $this->getUserUnidades($user['Id']);
                        $permisos = $this->getRolPermisos($user['IdRolSistema']);
                        unset($user['Password']);

                        $userData = [
                            'usuario' => $user,
                            'rol' => [
                                'id' => $user['IdRolSistema'],
                                'nombre' => $user['RolNombre'],
                                'descripcion' => $user['RolDescripcion']
                            ],
                            'division' => [
                                'id' => $user['IdDivisionAdm'],
                                'nombre' => $user['DivisionNombre'],
                                'pais' => $user['Pais'],
                                'region' => $user['Region'],
                                'ciudad' => $user['Ciudad']
                            ],
                            'unidades' => $unidades,
                            'permisos' => $permisos
                        ];

                        debugLog("Datos de usuario preparados correctamente");
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

        private function getRolPermisos($rolId) {
            $permisos = ['ver_dashboard'];

            switch ($rolId) {
                case 1:
                    $permisos = array_merge($permisos, [
                        'admin_usuarios', 'admin_roles', 'admin_unidades', 'admin_divisiones',
                        'crear_tramite', 'editar_tramite', 'eliminar_tramite', 
                        'ver_reportes', 'exportar_reportes', 'configuracion_sistema','admin_peticiones'
                    ]);
                    break;
                case 9:
                    $permisos = array_merge($permisos, [
                        'ver_dashboard', 'ver_departamentos'
                    ]);
                    break;
                case 10:
                    $permisos = array_merge($permisos, [
                        'ver_tablero'
                    ]);
                    break;
            }

            return $permisos;
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

        // Log de éxito
        $logFile = __DIR__ . '/logs/login_success.log';
        $logMessage = sprintf(
            "[%s] Login exitoso - User ID: %s, IP: %s, User Agent: %s\n",
            date('Y-m-d H:i:s'),
            $result['user']['usuario']['Id'],
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 200)
        );
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);

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
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}

debugLog("=== FIN LOGIN REQUEST ===");
?>