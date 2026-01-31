<?php
// Test para diagnosticar el problema con usuario-roles.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

echo "<h1>Test de usuario-roles.php</h1>";

// Test 1: Conexión a la base de datos
echo "<h2>1. Test de Conexión</h2>";
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color: green;'>✓ Conexión exitosa</p>";
} catch(Exception $e) {
    echo "<p style='color: red;'>✗ Error de conexión: " . $e->getMessage() . "</p>";
    exit;
}

// Test 2: Verificar que existe la tabla UsuarioRol
echo "<h2>2. Test de Tabla UsuarioRol</h2>";
try {
    $query = "SHOW TABLES LIKE 'UsuarioRol'";
    $stmt = $db->query($query);
    $result = $stmt->fetch();
    
    if($result) {
        echo "<p style='color: green;'>✓ Tabla UsuarioRol existe</p>";
    } else {
        echo "<p style='color: red;'>✗ Tabla UsuarioRol NO existe</p>";
        exit;
    }
} catch(Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    exit;
}

// Test 3: Verificar estructura de UsuarioRol
echo "<h2>3. Estructura de UsuarioRol</h2>";
try {
    $query = "DESCRIBE UsuarioRol";
    $stmt = $db->query($query);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th></tr>";
    foreach($columns as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch(Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

// Test 4: Verificar datos en UsuarioRol
echo "<h2>4. Datos en UsuarioRol</h2>";
try {
    $query = "SELECT COUNT(*) as count FROM UsuarioRol";
    $stmt = $db->query($query);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total de registros en UsuarioRol: <strong>" . $row['count'] . "</strong></p>";
    
    // Mostrar algunos registros
    $query = "SELECT * FROM UsuarioRol LIMIT 5";
    $stmt = $db->query($query);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if(!empty($rows)) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>";
        foreach(array_keys($rows[0]) as $key) {
            echo "<th>$key</th>";
        }
        echo "</tr>";
        foreach($rows as $row) {
            echo "<tr>";
            foreach($row as $value) {
                echo "<td>$value</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
} catch(Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

// Test 5: Probar la query específica getUsersByRole con idRol=13
echo "<h2>5. Test de getUsersByRole (idRol=13)</h2>";
try {
    $idRol = 13;
    $query = "SELECT 
                ur.IdUsuario,
                u.Nombre,
                u.ApellidoPaterno,
                u.ApellidoMaterno,
                CONCAT(u.Nombre, ' ', u.ApellidoPaterno, ' ', u.ApellidoMaterno) as NombreCompleto,
                u.Email,
                ur.FechaAsignacion
              FROM UsuarioRol ur
              JOIN Usuario u ON ur.IdUsuario = u.Id
              WHERE ur.IdRolSistema = :idRol
              ORDER BY u.Nombre, u.ApellidoPaterno";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':idRol', $idRol, PDO::PARAM_INT);
    $stmt->execute();

    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p style='color: green;'>✓ Query ejecutada correctamente</p>";
    echo "<p>Usuarios encontrados con IdRol=$idRol: <strong>" . count($usuarios) . "</strong></p>";
    
    if(!empty($usuarios)) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>";
        foreach(array_keys($usuarios[0]) as $key) {
            echo "<th>$key</th>";
        }
        echo "</tr>";
        foreach($usuarios as $row) {
            echo "<tr>";
            foreach($row as $value) {
                echo "<td>$value</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No hay usuarios con ese rol.</p>";
    }
} catch(Exception $e) {
    echo "<p style='color: red;'>✗ Error en query: " . $e->getMessage() . "</p>";
    echo "<p>Trace: " . $e->getTraceAsString() . "</p>";
}

// Test 6: Verificar que existe RolSistema con Id=13
echo "<h2>6. Test de RolSistema Id=13</h2>";
try {
    $query = "SELECT * FROM RolSistema WHERE Id = 13";
    $stmt = $db->query($query);
    $rol = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($rol) {
        echo "<p style='color: green;'>✓ Rol encontrado</p>";
        echo "<table border='1' cellpadding='5'>";
        foreach($rol as $key => $value) {
            echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠ No existe un rol con Id=13</p>";
    }
} catch(Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p>Test completado</p>";
?>
