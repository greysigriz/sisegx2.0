<?php
/**
 * Script mejorado para insertar datos de prueba
 * Detecta automáticamente la estructura de la tabla
 */

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "🔍 Analizando estructura de la tabla peticion_departamento...\n\n";
    
    // Obtener información detallada de las columnas
    $columnsQuery = "DESCRIBE peticion_departamento";
    $columnsStmt = $db->prepare($columnsQuery);
    $columnsStmt->execute();
    $columns = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Analizar qué columnas necesitan valores
    $requiredColumns = [];
    $optionalColumns = [];
    $autoIncrementCol = null;
    
    echo "📋 Estructura de la tabla:\n";
    echo "==========================\n";
    
    foreach ($columns as $col) {
        $field = $col['Field'];
        $type = $col['Type'];
        $null = $col['Null'];
        $key = $col['Key'];
        $default = $col['Default'];
        $extra = $col['Extra'];
        
        echo sprintf("%-30s | %-20s | %s\n", $field, $type, $null);
        
        // Si es auto_increment, saltarlo
        if (strpos($extra, 'auto_increment') !== false) {
            $autoIncrementCol = $field;
            continue;
        }
        
        // Si no permite NULL y no tiene valor por defecto, es requerido
        if ($null === 'NO' && $default === null && empty($extra)) {
            $requiredColumns[] = $field;
        } else {
            $optionalColumns[] = $field;
        }
    }
    
    echo "\n✅ Columnas requeridas: " . implode(', ', $requiredColumns) . "\n";
    echo "📌 Columna auto-increment: " . ($autoIncrementCol ?: 'ninguna') . "\n\n";
    
    // Preguntar si desea continuar
    echo "¿Deseas insertar datos de prueba? Esta operación insertará aproximadamente 50-100 registros.\n";
    echo "Escribe 'si' para continuar o cualquier otra cosa para cancelar: ";
    
    // Leer respuesta (comentado para ejecución automática)
    // $handle = fopen("php://stdin", "r");
    // $line = fgets($handle);
    // if (trim(strtolower($line)) !== 'si') {
    //     echo "❌ Operación cancelada.\n";
    //     exit;
    // }
    
    // Estados según el gráfico
    $estados = [
        'Esperando recepción',
        'Aceptado en proceso',
        'Devuelto a seguimiento',
        'Rechazado',
        'Completado'
    ];
    
    // IDs de ejemplo (ajustar según tu base de datos)
    $departamentos_ejemplo = [1, 2, 3, 4, 5];
    $usuarios_ejemplo = [1, 2, 3, 4, 5];
    $peticiones_ejemplo = range(1, 20);
    
    echo "📝 Insertando datos de prueba...\n\n";
    
    $registrosInsertados = 0;
    $errores = 0;
    
    // Generar datos para los últimos 30 días
    for ($diasAtras = 30; $diasAtras >= 0; $diasAtras--) {
        $fecha = date('Y-m-d', strtotime("-$diasAtras days"));
        
        // Cantidad variable de registros por día (1-6)
        $registrosPorDia = rand(1, 6);
        
        for ($i = 0; $i < $registrosPorDia; $i++) {
            $hora = sprintf('%02d:%02d:%02d', rand(8, 17), rand(0, 59), rand(0, 59));
            $fechaHora = "$fecha $hora";
            
            // Construir datos dinámicamente según columnas requeridas
            $datos = [];
            $placeholders = [];
            
            foreach ($requiredColumns as $column) {
                $placeholders[] = ":$column";
                
                switch ($column) {
                    case 'fecha_asignacion':
                    case 'fecha_registro':
                    case 'created_at':
                        $datos[$column] = $fechaHora;
                        break;
                        
                    case 'estado':
                        $datos[$column] = $estados[array_rand($estados)];
                        break;
                        
                    case 'id_departamento':
                    case 'departamento_id':
                        $datos[$column] = $departamentos_ejemplo[array_rand($departamentos_ejemplo)];
                        break;
                        
                    case 'id_usuario':
                    case 'usuario_id':
                    case 'id_asignado':
                        $datos[$column] = $usuarios_ejemplo[array_rand($usuarios_ejemplo)];
                        break;
                        
                    case 'id_peticion':
                    case 'peticion_id':
                        $datos[$column] = $peticiones_ejemplo[array_rand($peticiones_ejemplo)];
                        break;
                        
                    case 'observaciones':
                    case 'comentario':
                    case 'notas':
                        $datos[$column] = 'Registro de prueba generado automáticamente';
                        break;
                        
                    case 'prioridad':
                        $datos[$column] = ['Alta', 'Media', 'Baja'][array_rand(['Alta', 'Media', 'Baja'])];
                        break;
                        
                    default:
                        // Valor por defecto genérico
                        $datos[$column] = 1;
                }
            }
            
            if (empty($datos)) {
                echo "⚠️  No se pudieron generar datos. Verifica la estructura de la tabla.\n";
                break;
            }
            
            try {
                $columns_str = implode(', ', array_keys($datos));
                $placeholders_str = implode(', ', $placeholders);
                
                $query = "INSERT INTO peticion_departamento ($columns_str) VALUES ($placeholders_str)";
                $stmt = $db->prepare($query);
                $stmt->execute($datos);
                
                $registrosInsertados++;
                
                if ($registrosInsertados % 10 === 0) {
                    echo "✓ $registrosInsertados registros insertados...\n";
                }
                
            } catch (PDOException $e) {
                $errores++;
                if ($errores <= 3) {
                    echo "⚠️  Error en inserción: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✅ PROCESO COMPLETADO\n";
    echo str_repeat("=", 60) . "\n";
    echo "📊 Registros insertados exitosamente: $registrosInsertados\n";
    if ($errores > 0) {
        echo "⚠️  Errores encontrados: $errores\n";
    }
    
    // Mostrar resumen
    $resumenQuery = "
        SELECT 
            estado,
            COUNT(*) as total,
            DATE(MIN(fecha_asignacion)) as desde,
            DATE(MAX(fecha_asignacion)) as hasta
        FROM peticion_departamento
        WHERE fecha_asignacion >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY estado
        ORDER BY total DESC
    ";
    
    $resumenStmt = $db->prepare($resumenQuery);
    $resumenStmt->execute();
    $resumen = $resumenStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n📈 RESUMEN POR ESTADO (últimos 30 días):\n";
    echo str_repeat("-", 60) . "\n";
    
    foreach ($resumen as $row) {
        echo sprintf(
            "%-30s : %3d registros (%s a %s)\n",
            $row['estado'],
            $row['total'],
            $row['desde'],
            $row['hasta']
        );
    }
    
    echo "\n🎉 ¡Listo! Ahora recarga tu dashboard para ver el gráfico con datos.\n";
    echo "🌐 URL: http://localhost/SISEE (o la URL de tu proyecto)\n";
    
} catch (PDOException $e) {
    echo "\n❌ ERROR DE BASE DE DATOS\n";
    echo str_repeat("=", 60) . "\n";
    echo "Mensaje: " . $e->getMessage() . "\n";
    echo "\n💡 Soluciones posibles:\n";
    echo "1. Verifica que XAMPP esté corriendo\n";
    echo "2. Asegúrate de que la tabla 'peticion_departamento' existe\n";
    echo "3. Verifica la configuración en config/database.php\n";
    echo "4. Revisa los permisos de usuario en MySQL\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR GENERAL\n";
    echo str_repeat("=", 60) . "\n";
    echo $e->getMessage() . "\n";
}
