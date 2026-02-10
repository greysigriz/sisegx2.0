<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$dept_id = 17;

$query = "SELECT
            pd.id as asignacion_id,
            pd.estado as estado_departamento,
            pd.fecha_asignacion,
            p.id as peticion_id,
            p.folio,
            p.nombre,
            p.telefono,
            p.direccion,
            p.localidad,
            p.descripcion,
            p.red_social,
            p.fecha_registro as fecha_creacion,
            p.estado as estado_peticion,
            p.NivelImportancia,
            u.nombre_unidad as departamento_nombre,
            CONCAT(usr.Nombre, ' ', COALESCE(usr.ApellidoP, ''), ' ', COALESCE(usr.ApellidoM, '')) as usuario_seguimiento
          FROM peticion_departamento pd
          INNER JOIN peticiones p ON pd.peticion_id = p.id
          LEFT JOIN unidades u ON pd.departamento_id = u.id
          LEFT JOIN Usuario usr ON p.usuario_id = usr.Id
          WHERE pd.departamento_id = ?
          ORDER BY pd.fecha_asignacion DESC";

try {
    $stmt = $db->prepare($query);
    $stmt->execute([$dept_id]);
    $peticiones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ Query ejecutado correctamente\n";
    echo "📊 Total de peticiones: " . count($peticiones) . "\n\n";
    
    if (count($peticiones) > 0) {
        echo "Primera petición:\n";
        echo "  - Folio: " . $peticiones[0]['folio'] . "\n";
        echo "  - Nombre: " . $peticiones[0]['nombre'] . "\n";
        echo "  - Estado: " . $peticiones[0]['estado_departamento'] . "\n";
        echo "  - Fecha asignación: " . $peticiones[0]['fecha_asignacion'] . "\n";
    } else {
        echo "No se encontraron peticiones para el departamento ID: $dept_id\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Error en el query:\n";
    echo $e->getMessage() . "\n";
}
?>