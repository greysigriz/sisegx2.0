<?php
// C:\xampp\htdocs\SISE\models\Jerarquia.php
class Jerarquia {
    // Conexión a la base de datos y nombre de la tabla
    private $conn;
    private $table_name = "JerarquiaRol";
    
    // Propiedades del objeto
    public $IdRolSuperior;
    public $IdRolSubordinado;
    
    // Constructor con conexión a la base de datos
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Leer todas las jerarquías
    public function read() {
        // Consulta SQL
        $query = "SELECT 
                    jr.IdRolSuperior, 
                    jr.IdRolSubordinado,
                    r1.Nombre as NombreRolSuperior,
                    r2.Nombre as NombreRolSubordinado
                FROM 
                    " . $this->table_name . " jr
                JOIN 
                    RolSistema r1 ON jr.IdRolSuperior = r1.Id
                JOIN 
                    RolSistema r2 ON jr.IdRolSubordinado = r2.Id
                ORDER BY 
                    r1.Nombre ASC, r2.Nombre ASC";
        
        // Preparar consulta
        $stmt = $this->conn->prepare($query);
        
        // Ejecutar consulta
        $stmt->execute();
        
        return $stmt;
    }
    
    // Crear relación jerárquica
    public function create() {
        // Consulta SQL
        $query = "INSERT INTO " . $this->table_name . "
                (IdRolSuperior, IdRolSubordinado)
                VALUES
                (:IdRolSuperior, :IdRolSubordinado)";
        
        // Preparar consulta
        $stmt = $this->conn->prepare($query);
        
        // Sanitizar
        $this->IdRolSuperior = htmlspecialchars(strip_tags($this->IdRolSuperior));
        $this->IdRolSubordinado = htmlspecialchars(strip_tags($this->IdRolSubordinado));
        
        // Vincular valores
        $stmt->bindParam(":IdRolSuperior", $this->IdRolSuperior);
        $stmt->bindParam(":IdRolSubordinado", $this->IdRolSubordinado);
        
        // Ejecutar consulta
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Eliminar relación jerárquica
    public function delete() {
        // Consulta SQL
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE IdRolSuperior = :IdRolSuperior 
                  AND IdRolSubordinado = :IdRolSubordinado";
        
        // Preparar consulta
        $stmt = $this->conn->prepare($query);
        
        // Sanitizar
        $this->IdRolSuperior = htmlspecialchars(strip_tags($this->IdRolSuperior));
        $this->IdRolSubordinado = htmlspecialchars(strip_tags($this->IdRolSubordinado));
        
        // Vincular valores
        $stmt->bindParam(":IdRolSuperior", $this->IdRolSuperior);
        $stmt->bindParam(":IdRolSubordinado", $this->IdRolSubordinado);
        
        // Ejecutar consulta
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Eliminar todas las jerarquías de un rol (ya sea como superior o subordinado)
    public function deleteByRolId($rolId) {
        // Consulta SQL
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE IdRolSuperior = :rolId OR IdRolSubordinado = :rolId";
        
        // Preparar consulta
        $stmt = $this->conn->prepare($query);
        
        // Sanitizar
        $rolId = htmlspecialchars(strip_tags($rolId));
        
        // Vincular valores
        $stmt->bindParam(":rolId", $rolId);
        
        // Ejecutar consulta
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Verificar si existe un ciclo en la jerarquía
    public function verificarCiclo($rolIdInicio, $rolIdFin) {
        // Esta implementación utiliza una búsqueda en profundidad (DFS)
        // para detectar ciclos en un grafo dirigido
        
        // Conjunto para marcar nodos visitados
        $visitados = array();
        
        // Función recursiva para DFS
        function dfs($conn, $nodoActual, $objetivo, &$visitados) {
            // Si llegamos al objetivo, existe un camino
            if ($nodoActual == $objetivo) {
                return true;
            }
            
            // Marcar como visitado
            $visitados[$nodoActual] = true;
            
            // Buscar todos los roles subordinados del nodo actual
            $query = "SELECT IdRolSubordinado FROM JerarquiaRol WHERE IdRolSuperior = ?";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(1, $nodoActual);
            $stmt->execute();
            
            // Recorrer cada subordinado
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $subordinado = $row['IdRolSubordinado'];
                
                // Si no fue visitado aún, realizar DFS
                if (!isset($visitados[$subordinado])) {
                    if (dfs($conn, $subordinado, $objetivo, $visitados)) {
                        return true;
                    }
                }
            }
            
            return false;
        }
        
        // Iniciar DFS desde rolIdFin para buscar un camino hasta rolIdInicio
        // Si existe tal camino, agregar rolIdInicio como superior de rolIdFin crearía un ciclo
        return dfs($this->conn, $rolIdFin, $rolIdInicio, $visitados);
    }
}
?>