<?php
// Constantes compartidas del sistema SISEE

// Estados finales de peticiones (no requieren acción)
define('ESTADOS_FINALES', ['Completada', 'Cancelada', 'Improcedente']);

// Estados críticos (requieren atención inmediata)
define('ESTADOS_CRITICOS', ['Sin revisar', 'Pendiente', 'Esperando recepción']);

// Estados finales de departamento
define('ESTADOS_FINALES_DEPTO', ['Completado', 'Cerrado']);

// Días para considerar petición retrasada
define('DIAS_RETRASO_PETICION', 30);
define('DIAS_RETRASO_DEPARTAMENTO', 15);

// Roles del sistema
define('ROL_SUPER_USUARIO', 1);
define('ROL_ADMIN_2', 2);
define('ROL_DIRECTOR', 10);
define('ROL_DEPARTAMENTO', 9);
define('ROL_CANALIZADOR_MUNICIPAL', 12);
define('ROL_CANALIZADOR_ESTATAL', 13);

define('ROLES_ADMIN', [ROL_SUPER_USUARIO, ROL_ADMIN_2, ROL_DIRECTOR]);
define('ROLES_CANALIZADOR', [ROL_CANALIZADOR_MUNICIPAL, ROL_CANALIZADOR_ESTATAL]);
define('ROLES_CON_DRILLDOWN', [ROL_SUPER_USUARIO, ROL_ADMIN_2, ROL_DIRECTOR, ROL_CANALIZADOR_MUNICIPAL, ROL_CANALIZADOR_ESTATAL]);
