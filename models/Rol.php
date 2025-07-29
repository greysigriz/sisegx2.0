<?php
// C:\xampp\htdocs\SISE\models\Rol.php
class Rol {
    // Conexión a la base de datos y nombre de la tabla
    private $conn;
    private $table_name = "RolSistema";
    
    // Propiedades del objeto
    public $Id;
    public $Nombre;
    public $Descripcion;
    public $FechaCreacion;
    
    // Constructor con conexión a la base de datos
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Leer todos los roles
    public function read() {
        // Consulta SQL
        $query = "SELECT 
                    Id, 
                    Nombre, 
                    Descripcion, 
                    FechaCreacion
                FROM 
                    " . $this->table_name . "
                ORDER BY 
                    Nombre ASC";
        
        // Preparar consulta
        $stmt = $this->conn->prepare($query);
        
        // Ejecutar consulta
        $stmt->execute();
        
        return $stmt;
    }
    
    // Leer un rol específico
    public function readOne() {
        // Consulta SQL
        $query = "SELECT 
                    Id, 
                    Nombre, 
                    Descripcion, 
                    FechaCreacion
                FROM 
                    " . $this->table_name . "
                WHERE 
                    Id = ?
                LIMIT 0,1";
        
        // Preparar consulta
        $stmt = $this->conn->prepare($query);
        
        // Vincular ID
        $stmt->bindParam(1, $this->Id);
        
        // Ejecutar consulta
        $stmt->execute();
        
        // Obtener fila
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            // Asignar valores a las propiedades del objeto
            $this->Id = $row['Id'];
            $this->Nombre = $row['Nombre'];
            $this->Descripcion = $row['Descripcion'];
            $this->FechaCreacion = $row['FechaCreacion'];
            return true;
        }
        
        return false;
    }
    
    // Crear rol
    public function create() {
        // Consulta SQL
        $query = "INSERT INTO " . $this->table_name . "
                (Nombre, Descripcion)
                VALUES
                (:Nombre, :Descripcion)";
        
        // Preparar consulta
        $stmt = $this->conn->prepare($query);
        
        // Sanitizar
        $this->Nombre = htmlspecialchars(strip_tags($this->Nombre));
        $this->Descripcion = htmlspecialchars(strip_tags($this->Descripcion));
        
        // Vincular valores
        $stmt->bindParam(":Nombre", $this->Nombre);
        $stmt->bindParam(":Descripcion", $this->Descripcion);
        
        // Ejecutar consulta
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Actualizar rol
    public function update() {
        // Consulta SQL
        $query = "UPDATE " . $this->table_name . "
                SET
                    Nombre = :Nombre,
                    Descripcion = :Descripcion
                WHERE 
                    Id = :Id";
        
        // Preparar consulta
        $stmt = $this->conn->prepare($query);
        
        // Sanitizar
        $this->Id = htmlspecialchars(strip_tags($this->Id));
        $this->Nombre = htmlspecialchars(strip_tags($this->Nombre));
        $this->Descripcion = htmlspecialchars(strip_tags($this->Descripcion));
        
        // Vincular valores
        $stmt->bindParam(":Id", $this->Id);
        $stmt->bindParam(":Nombre", $this->Nombre);
        $stmt->bindParam(":Descripcion", $this->Descripcion);
        
        // Ejecutar consulta
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Eliminar rol
    public function delete() {
        // Consulta SQL
        $query = "DELETE FROM " . $this->table_name . " WHERE Id = ?";
        
        // Preparar consulta
        $stmt = $this->conn->prepare($query);
        
        // Sanitizar
        $this->Id = htmlspecialchars(strip_tags($this->Id));
        
        // Vincular ID
        $stmt->bindParam(1, $this->Id);
        
        // Ejecutar consulta
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Verificar si un rol tiene usuarios asignados
    public function tieneUsuarios() {
        $query = "SELECT COUNT(*) as total FROM Usuario WHERE IdRolSistema = ?";
        
        // Preparar consulta
        $stmt = $this->conn->prepare($query);
        
        // Vincular ID
        $stmt->bindParam(1, $this->Id);
        
        // Ejecutar consulta
        $stmt->execute();
        
        // Obtener resultado
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($row['total'] > 0);
    }
    
    // Obtener roles subordinados
    public function obtenerSubordinados() {
        $query = "SELECT 
                    r.Id, 
                    r.Nombre, 
                    r.Descripcion
                FROM 
                    JerarquiaRol jr
                JOIN 
                    " . $this->table_name . " r ON jr.IdRolSubordinado = r.Id
                WHERE 
                    jr.IdRolSuperior = ?
                ORDER BY 
                    r.Nombre ASC";
                    
        // Preparar consulta
        $stmt = $this->conn->prepare($query);
        
        // Vincular ID
        $stmt->bindParam(1, $this->Id);
        
        // Ejecutar consulta
        $stmt->execute();
        
        return $stmt;
    }
    
    // Obtener roles superiores
    public function obtenerSuperiores() {
        $query = "SELECT 
                    r.Id, 
                    r.Nombre, 
                    r.Descripcion
                FROM 
                    JerarquiaRol jr
                JOIN 
                    " . $this->table_name . " r ON jr.IdRolSuperior = r.Id
                WHERE 
                    jr.IdRolSubordinado = ?
                ORDER BY 
                    r.Nombre ASC";
                    
        // Preparar consulta
        $stmt = $this->conn->prepare($query);
        
        // Vincular ID
        $stmt->bindParam(1, $this->Id);
        
        // Ejecutar consulta
        $stmt->execute();
        
        return $stmt;
    }
}
?>