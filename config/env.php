<?php
// C:\xampp\htdocs\SISEE\config\env.php
/**
 * Cargador de variables de entorno desde archivo .env
 * Inspirado en vlucas/phpdotenv pero simplificado
 */

class EnvLoader {
    private static $loaded = false;
    
    /**
     * Cargar variables de entorno desde archivo .env
     */
    public static function load($path = null) {
        if (self::$loaded) {
            return; // Ya cargado, no recargar
        }
        
        if ($path === null) {
            $path = dirname(__DIR__) . '/.env';
        }
        
        if (!file_exists($path)) {
            // Si no existe .env, buscar .env.example
            $examplePath = dirname(__DIR__) . '/.env.example';
            if (file_exists($examplePath)) {
                error_log("ADVERTENCIA: No se encontró archivo .env. Usa .env.example como plantilla.");
            }
            return; // No cargar nada si no existe el archivo
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Ignorar comentarios y líneas vacías
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            
            // Parsear línea KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remover comillas si existen
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                // Solo establecer si no existe ya como variable de entorno
                if (!getenv($key)) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }
        
        self::$loaded = true;
    }
    
    /**
     * Obtener variable de entorno con valor por defecto
     */
    public static function get($key, $default = null) {
        $value = getenv($key);
        return $value !== false ? $value : $default;
    }
    
    /**
     * Verificar si una variable de entorno está definida
     */
    public static function has($key) {
        return getenv($key) !== false;
    }
}

// Auto-cargar al incluir este archivo
EnvLoader::load();
