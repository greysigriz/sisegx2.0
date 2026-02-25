<?php
// C:\xampp\htdocs\SISEE\api\services\EstadoService.php

/**
 * Servicio para gestionar el estado automático de peticiones
 * basado en los estados de los departamentos asignados
 */
class EstadoService {
    
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Calcula y actualiza automáticamente el estado de una petición
     * basado en los estados de sus departamentos asignados
     * 
     * @param int $peticion_id ID de la petición
     * @return array Resultado con el nuevo estado y detalles
     */
    public function actualizarEstadoAutomatico($peticion_id) {
        try {
            // Obtener todos los departamentos asignados a la petición
            $query = "SELECT estado FROM peticion_departamento WHERE peticion_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$peticion_id]);
            $departamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Si no hay departamentos asignados
            if (empty($departamentos)) {
                $nuevoEstado = 'Por asignar departamento';
                $this->actualizarEstadoPeticion($peticion_id, $nuevoEstado);
                return [
                    'success' => true,
                    'estado_anterior' => $this->getEstadoActual($peticion_id),
                    'estado_nuevo' => $nuevoEstado,
                    'razon' => 'No hay departamentos asignados',
                    'requiere_atencion' => true,
                    'prioridad' => 'alta'
                ];
            }
            
            // Contar estados de los departamentos
            $conteoEstados = [
                'Completado' => 0,
                'Rechazado' => 0,
                'Aceptado en proceso' => 0,
                'Devuelto a seguimiento' => 0,
                'Esperando recepción' => 0
            ];
            
            foreach ($departamentos as $dept) {
                $estado = $dept['estado'];
                if (isset($conteoEstados[$estado])) {
                    $conteoEstados[$estado]++;
                }
            }
            
            $totalDepartamentos = count($departamentos);
            $estadoAnterior = $this->getEstadoActual($peticion_id);
            
            // Lógica de determinación de estado automático
            $resultado = $this->determinarEstado($conteoEstados, $totalDepartamentos);
            
            // Solo actualizar si el estado cambió
            if ($resultado['estado'] !== $estadoAnterior) {
                $this->actualizarEstadoPeticion($peticion_id, $resultado['estado']);
                
                // Registrar el cambio automático en el historial (opcional)
                $this->registrarCambioAutomatico($peticion_id, $estadoAnterior, $resultado['estado'], $resultado['razon']);
            }
            
            return [
                'success' => true,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $resultado['estado'],
                'razon' => $resultado['razon'],
                'requiere_atencion' => $resultado['requiere_atencion'],
                'prioridad' => $resultado['prioridad'],
                'conteo_departamentos' => $conteoEstados
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al actualizar estado automático: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Determina el estado de la petición según los estados de los departamentos
     */
    private function determinarEstado($conteoEstados, $totalDepartamentos) {
        // Regla 1: Todos completados → Completado
        if ($conteoEstados['Completado'] === $totalDepartamentos) {
            return [
                'estado' => 'Completado',
                'razon' => 'Todos los departamentos completaron la petición',
                'requiere_atencion' => false,
                'prioridad' => 'baja'
            ];
        }
        
        // Regla 2: Todos rechazados → Rechazado por departamento
        if ($conteoEstados['Rechazado'] === $totalDepartamentos) {
            return [
                'estado' => 'Rechazado por departamento',
                'razon' => 'Todos los departamentos rechazaron la petición',
                'requiere_atencion' => true,
                'prioridad' => 'alta'
            ];
        }
        
        // Regla 3: Al menos uno devuelto → Devuelto (requiere atención)
        if ($conteoEstados['Devuelto a seguimiento'] > 0) {
            return [
                'estado' => 'Devuelto',
                'razon' => $conteoEstados['Devuelto a seguimiento'] . ' departamento(s) devolvieron la petición',
                'requiere_atencion' => true,
                'prioridad' => 'alta'
            ];
        }
        
        // Regla 4: Al menos uno en proceso → Aceptada en proceso
        if ($conteoEstados['Aceptado en proceso'] > 0) {
            $completados = $conteoEstados['Completado'];
            $enProceso = $conteoEstados['Aceptado en proceso'];
            return [
                'estado' => 'Aceptada en proceso',
                'razon' => "$enProceso departamento(s) trabajando ($completados de $totalDepartamentos completados)",
                'requiere_atencion' => false,
                'prioridad' => 'media'
            ];
        }
        
        // Regla 5: Algunos rechazados pero no todos → Aceptada en proceso
        if ($conteoEstados['Rechazado'] > 0 && $conteoEstados['Rechazado'] < $totalDepartamentos) {
            return [
                'estado' => 'Aceptada en proceso',
                'razon' => 'Algunos departamentos rechazaron, otros continúan',
                'requiere_atencion' => true,
                'prioridad' => 'media'
            ];
        }
        
        // Regla 6: Todos esperando recepción → Esperando recepción
        if ($conteoEstados['Esperando recepción'] === $totalDepartamentos) {
            return [
                'estado' => 'Esperando recepción',
                'razon' => 'Esperando que los departamentos reciban la petición',
                'requiere_atencion' => true,
                'prioridad' => 'media'
            ];
        }
        
        // Regla por defecto: Aceptada en proceso
        return [
            'estado' => 'Aceptada en proceso',
            'razon' => 'Estado mixto en departamentos',
            'requiere_atencion' => false,
            'prioridad' => 'media'
        ];
    }
    
    /**
     * Actualiza el estado de la petición en la base de datos
     */
    private function actualizarEstadoPeticion($peticion_id, $estado) {
        $query = "UPDATE peticiones SET estado = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$estado, $peticion_id]);
    }
    
    /**
     * Obtiene el estado actual de la petición
     */
    private function getEstadoActual($peticion_id) {
        $query = "SELECT estado FROM peticiones WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$peticion_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['estado'] : null;
    }
    
    /**
     * Registra el cambio automático de estado (opcional)
     */
    private function registrarCambioAutomatico($peticion_id, $estadoAnterior, $estadoNuevo, $razon) {
        // Esto se puede implementar si tienes una tabla de historial
        // Por ahora solo lo dejamos como placeholder
        error_log("Petición $peticion_id: Estado cambió de '$estadoAnterior' a '$estadoNuevo'. Razón: $razon");
    }
    
    /**
     * Obtiene información completa del estado de una petición
     * incluyendo estadísticas de departamentos
     */
    public function getEstadoCompleto($peticion_id) {
        try {
            $query = "SELECT 
                        p.estado as estado_peticion,
                        p.NivelImportancia,
                        COUNT(pd.id) as total_departamentos,
                        SUM(CASE WHEN pd.estado = 'Completado' THEN 1 ELSE 0 END) as completados,
                        SUM(CASE WHEN pd.estado = 'Rechazado' THEN 1 ELSE 0 END) as rechazados,
                        SUM(CASE WHEN pd.estado = 'Aceptado en proceso' THEN 1 ELSE 0 END) as en_proceso,
                        SUM(CASE WHEN pd.estado = 'Devuelto a seguimiento' THEN 1 ELSE 0 END) as devueltos,
                        SUM(CASE WHEN pd.estado = 'Esperando recepción' THEN 1 ELSE 0 END) as esperando
                      FROM peticiones p
                      LEFT JOIN peticion_departamento pd ON p.id = pd.peticion_id
                      WHERE p.id = ?
                      GROUP BY p.id";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$peticion_id]);
            $info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$info) {
                return null;
            }
            
            // Calcular progreso
            $total = intval($info['total_departamentos']);
            $completados = intval($info['completados']);
            $progreso = $total > 0 ? round(($completados / $total) * 100, 1) : 0;
            
            // Determinar si requiere atención
            $requiereAtencion = $this->requiereAtencion($info);
            
            return [
                'estado_peticion' => $info['estado_peticion'],
                'nivel_importancia' => intval($info['NivelImportancia']),
                'total_departamentos' => $total,
                'completados' => $completados,
                'rechazados' => intval($info['rechazados']),
                'en_proceso' => intval($info['en_proceso']),
                'devueltos' => intval($info['devueltos']),
                'esperando' => intval($info['esperando']),
                'progreso_porcentaje' => $progreso,
                'requiere_atencion' => $requiereAtencion['requiere'],
                'razon_atencion' => $requiereAtencion['razon'],
                'prioridad' => $this->calcularPrioridad($info)
            ];
            
        } catch (Exception $e) {
            error_log("Error en getEstadoCompleto: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Determina si una petición requiere atención
     */
    private function requiereAtencion($info) {
        $razones = [];
        
        // Estado sin revisar
        if ($info['estado_peticion'] === 'Sin revisar') {
            $razones[] = 'Petición sin revisar';
        }
        
        // Sin departamentos asignados
        if ($info['total_departamentos'] == 0) {
            $razones[] = 'Sin departamentos asignados';
        }
        
        // Departamentos devueltos
        if ($info['devueltos'] > 0) {
            $razones[] = $info['devueltos'] . ' departamento(s) devolvieron';
        }
        
        // Todos rechazaron
        if ($info['rechazados'] > 0 && $info['rechazados'] == $info['total_departamentos']) {
            $razones[] = 'Todos los departamentos rechazaron';
        }
        
        // Esperando mucho tiempo (esto requeriría comparar fechas)
        if ($info['esperando'] == $info['total_departamentos'] && $info['total_departamentos'] > 0) {
            $razones[] = 'Esperando recepción de departamentos';
        }
        
        return [
            'requiere' => count($razones) > 0,
            'razon' => implode(', ', $razones)
        ];
    }
    
    /**
     * Calcula la prioridad visual de la petición
     */
    private function calcularPrioridad($info) {
        // Basado en nivel de importancia y estado
        $nivel = intval($info['NivelImportancia']);
        
        if ($info['devueltos'] > 0 || ($info['rechazados'] > 0 && $info['rechazados'] == $info['total_departamentos'])) {
            return 'critica';
        }
        
        if ($nivel <= 2) {
            return 'alta';
        } elseif ($nivel === 3) {
            return 'media';
        } else {
            return 'baja';
        }
    }
}
?>
