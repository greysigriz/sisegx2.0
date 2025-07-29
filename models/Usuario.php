<?php
// C:\xampp\htdocs\SISE\models\Usuario.php
class Usuario {
    // Conexión a la base de datos y nombre de la tabla
    private $conn;
    private $table_name = "Usuario";
    
    // Propiedades del objeto
    public $Id;
    public $Usuario;
    public $Nombre;
    public $ApellidoP;
    public $ApellidoM;
    public $Puesto;
    public $Estatus;
    public $IdDivisionAdm;
    public $IdUnidad;
    public $IdRolSistema;
    public $Password;
    public $FechaCreacion;
    
    // Constructor con conexión a la base de datos
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Leer todos los usuarios
    public function read() {
        // Consulta SQL
        $query = "SELECT 
                    u.Id, 
                    u.Usuario, 
                    u.Nombre, 
                    u.ApellidoP, 
                    u.ApellidoM, 
                    u.Puesto, 
                    u.Estatus, 
                    u.IdDivisionAdm, 
                    u.IdUnidad, 
                    u.IdRolSistema,
                    r.Nombre AS NombreRol,
                    d.Nombre AS NombreDivision
                FROM 
                    " . $this->table_name . " u
                LEFT JOIN 
                    RolSistema r ON u.IdRolSistema = r.Id
                LEFT JOIN 
                    DivisionAdministrativa d ON u.IdDivisionAdm = d.Id
                ORDER BY 
                    u.Id DESC";
        
        // Preparar consulta
        $stmt = $this->conn->prepare($query);
        
        // Ejecutar consulta
        $stmt->execute();
        
        return $stmt;
    }
    
    // Leer un usuario
    public function readOne() {
        // Consulta SQL
        $query = "SELECT 
                    u.Id, 
                    u.Usuario, 
                    u.Nombre, 
                    u.ApellidoP, 
                    u.ApellidoM, 
                    u.Puesto, 
                    u.Estatus, 
                    u.IdDivisionAdm, 
                    u.IdUnidad, 
                    u.IdRolSistema,
                    r.Nombre AS NombreRol,
                    d.Nombre AS NombreDivision
                FROM 
                    " . $this->table_name . " u
                LEFT JOIN 
                    RolSistema r ON u.IdRolSistema = r.Id
                LEFT JOIN 
                    DivisionAdministrativa d ON u.IdDivisionAdm = d.Id
                WHERE 
                    u.Id = ?
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
            return $row;
        }
        
        return false;
    }
    
    // Crear usuario
    public function create() {
        // Consulta SQL
        $query = "INSERT INTO " . $this->table_name . "
                (Usuario, Nombre, ApellidoP, ApellidoM, Puesto, Estatus, IdDivisionAdm, IdUnidad, IdRolSistema, Password)
                VALUES
                (:Usuario, :Nombre, :ApellidoP, :ApellidoM, :Puesto, :Estatus, :IdDivisionAdm, :IdUnidad, :IdRolSistema, :Password)";
        
        // Preparar consulta
        $stmt = $this->conn->prepare($query);
        
        // Sanitizar
        $this->Usuario = htmlspecialchars(strip_tags($this->Usuario));
        $this->Nombre = htmlspecialchars(strip_tags($this->Nombre));
        $this->ApellidoP = htmlspecialchars(strip_tags($this->ApellidoP));
        $this->ApellidoM = htmlspecialchars(strip_tags($this->ApellidoM));
        $this->Puesto = htmlspecialchars(strip_tags($this->Puesto));
        $this->Estatus = htmlspecialchars(strip_tags($this->Estatus));
        
        // Vincular valores
        $stmt->bindParam(":Usuario", $this->Usuario);
        $stmt->bindParam(":Nombre", $this->Nombre);
        $stmt->bindParam(":ApellidoP", $this->ApellidoP);
        $stmt->bindParam(":ApellidoM", $this->ApellidoM);
        $stmt->bindParam(":Puesto", $this->Puesto);
        $stmt->bindParam(":Estatus", $this->Estatus);
        $stmt->bindParam(":IdDivisionAdm", $this->IdDivisionAdm);
        $stmt->bindParam(":IdUnidad", $this->IdUnidad);
        $stmt->bindParam(":IdRolSistema", $this->IdRolSistema);
        $stmt->bindParam(":Password", $this->Password);
        
        // Ejecutar consulta
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Actualizar usuario
    public function update() {
        // Consulta SQL base
        $query = "UPDATE " . $this->table_name . "
                SET
                    Usuario = :Usuario,
                    Nombre = :Nombre,
                    ApellidoP = :ApellidoP,
                    ApellidoM = :ApellidoM,
                    Puesto = :Puesto,
                    Estatus = :Estatus,
                    IdDivisionAdm = :IdDivisionAdm,
                    IdUnidad = :IdUnidad,
                    IdRolSistema = :IdRolSistema";
        
        // Si hay contraseña, incluirla en la actualización
        if(!empty($this->Password)) {
            $query .= ", Password = :Password";
        }
        
        $query .= " WHERE Id = :Id";
        
        // Preparar consulta
        $stmt = $this->conn->prepare($query);
        
        // Sanitizar
        $this->Usuario = htmlspecialchars(strip_tags($this->Usuario));
        $this->Nombre = htmlspecialchars(strip_tags($this->Nombre));
        $this->ApellidoP = htmlspecialchars(strip_tags($this->ApellidoP));
        $this->ApellidoM = htmlspecialchars(strip_tags($this->ApellidoM));
        $this->Puesto = htmlspecialchars(strip_tags($this->Puesto));
        $this->Estatus = htmlspecialchars(strip_tags($this->Estatus));
        $this->Id = htmlspecialchars(strip_tags($this->Id));
        
        // Vincular valores
        $stmt->bindParam(":Usuario", $this->Usuario);
        $stmt->bindParam(":Nombre", $this->Nombre);
        $stmt->bindParam(":ApellidoP", $this->ApellidoP);
        $stmt->bindParam(":ApellidoM", $this->ApellidoM);
        $stmt->bindParam(":Puesto", $this->Puesto);
        $stmt->bindParam(":Estatus", $this->Estatus);
        $stmt->bindParam(":IdDivisionAdm", $this->IdDivisionAdm);
        $stmt->bindParam(":IdUnidad", $this->IdUnidad);
        $stmt->bindParam(":IdRolSistema", $this->IdRolSistema);
        $stmt->bindParam(":Id", $this->Id);
        
        // Vincular contraseña si está presente
        if(!empty($this->Password)) {
            $stmt->bindParam(":Password", $this->Password);
        }
        
        // Ejecutar consulta
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    
    // Eliminar usuario
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
}
?>