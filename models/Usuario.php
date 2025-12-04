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
    
    // Constructor con conexión a la base de datos
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Leer todos los usuarios
    public function read() {
        // Consulta SQL
        $query = "SELECT 
                    u.Id, u.Usuario, u.Nombre, u.ApellidoP, u.ApellidoM, 
                    u.Puesto, u.Estatus, u.IdDivisionAdm, u.IdUnidad, u.IdRolSistema,
                    r.Nombre as NombreRol,
                    d.Municipio as NombreDivision
                  FROM " . $this->table_name . " u
                  LEFT JOIN RolSistema r ON u.IdRolSistema = r.Id
                  LEFT JOIN DivisionAdministrativa d ON u.IdDivisionAdm = d.Id
                  ORDER BY u.Id DESC";
        
        // Preparar consulta
        $stmt = $this->conn->prepare($query);
        // Ejecutar consulta
        $stmt->execute();
        
        return $stmt;
    }
    
    // Leer un usuario específico
    public function readOne() {
        // Consulta SQL
        $query = "SELECT 
                    u.Id, u.Usuario, u.Nombre, u.ApellidoP, u.ApellidoM, 
                    u.Puesto, u.Estatus, u.IdDivisionAdm, u.IdUnidad, u.IdRolSistema,
                    r.Nombre as NombreRol,
                    d.Municipio as NombreDivision
                  FROM " . $this->table_name . " u
                  LEFT JOIN RolSistema r ON u.IdRolSistema = r.Id
                  LEFT JOIN DivisionAdministrativa d ON u.IdDivisionAdm = d.Id
                  WHERE u.Id = ?
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
                  SET Usuario=:usuario, Nombre=:nombre, ApellidoP=:apellidoP, 
                      ApellidoM=:apellidoM, Puesto=:puesto, Estatus=:estatus,
                      IdDivisionAdm=:idDivisionAdm, IdUnidad=:idUnidad, 
                      IdRolSistema=:idRolSistema, Password=:password";
        
        // Preparar consulta
        $stmt = $this->conn->prepare($query);
        
        // Vincular valores
        $stmt->bindParam(":usuario", $this->Usuario);
        $stmt->bindParam(":nombre", $this->Nombre);
        $stmt->bindParam(":apellidoP", $this->ApellidoP);
        $stmt->bindParam(":apellidoM", $this->ApellidoM);
        $stmt->bindParam(":puesto", $this->Puesto);
        $stmt->bindParam(":estatus", $this->Estatus);
        $stmt->bindParam(":idDivisionAdm", $this->IdDivisionAdm);
        $stmt->bindParam(":idUnidad", $this->IdUnidad);
        $stmt->bindParam(":idRolSistema", $this->IdRolSistema);
        $stmt->bindParam(":password", $this->Password);
        
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
                  SET Usuario=:usuario, Nombre=:nombre, ApellidoP=:apellidoP, 
                      ApellidoM=:apellidoM, Puesto=:puesto, Estatus=:estatus,
                      IdDivisionAdm=:idDivisionAdm, IdUnidad=:idUnidad, 
                      IdRolSistema=:idRolSistema";
        
        // Si se proporciona contraseña, actualizarla
        if(!empty($this->Password)) {
            $query .= ", Password=:password";
        }
        
        $query .= " WHERE Id=:id";
        
        // Preparar consulta
        $stmt = $this->conn->prepare($query);
        
        // Vincular valores
        $stmt->bindParam(":usuario", $this->Usuario);
        $stmt->bindParam(":nombre", $this->Nombre);
        $stmt->bindParam(":apellidoP", $this->ApellidoP);
        $stmt->bindParam(":apellidoM", $this->ApellidoM);
        $stmt->bindParam(":puesto", $this->Puesto);
        $stmt->bindParam(":estatus", $this->Estatus);
        $stmt->bindParam(":idDivisionAdm", $this->IdDivisionAdm);
        $stmt->bindParam(":idUnidad", $this->IdUnidad);
        $stmt->bindParam(":idRolSistema", $this->IdRolSistema);
        $stmt->bindParam(":id", $this->Id);
        
        // Vincular contraseña si está presente
        if(!empty($this->Password)) {
            $stmt->bindParam(":password", $this->Password);
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