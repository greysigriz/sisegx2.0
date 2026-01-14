// C:\xampp\htdocs\SISEE\src\utils\rolesHelper.js
/**
 * Helper para manejar verificación de roles en el sistema
 * Soporta múltiples roles por usuario
 */

/**
 * Obtener los roles del usuario desde localStorage
 * @returns {Array} Array de objetos de roles
 */
export function getUserRoles() {
  try {
    const user = JSON.parse(localStorage.getItem('user'));
    if (!user) return [];

    // Soportar el nuevo formato con múltiples roles
    if (user.Roles && Array.isArray(user.Roles)) {
      return user.Roles;
    }

    // Compatibilidad con el formato antiguo (un solo rol)
    if (user.IdRolSistema) {
      return [{
        Id: user.IdRolSistema,
        Nombre: user.RolNombre || 'Usuario',
        Descripcion: ''
      }];
    }

    return [];
  } catch (error) {
    console.error('Error al obtener roles:', error);
    return [];
  }
}

/**
 * Obtener los IDs de roles del usuario
 * @returns {Array<number>} Array de IDs de roles
 */
export function getUserRolesIds() {
  try {
    const user = JSON.parse(localStorage.getItem('user'));
    if (!user) return [];

    // Nuevo formato
    if (user.RolesIds && Array.isArray(user.RolesIds)) {
      return user.RolesIds;
    }

    // Compatibilidad con formato antiguo
    if (user.IdRolSistema) {
      return [user.IdRolSistema];
    }

    return [];
  } catch (error) {
    console.error('Error al obtener IDs de roles:', error);
    return [];
  }
}

/**
 * Obtener los nombres de roles del usuario
 * @returns {Array<string>} Array de nombres de roles
 */
export function getUserRolesNames() {
  try {
    const user = JSON.parse(localStorage.getItem('user'));
    if (!user) return [];

    // Nuevo formato
    if (user.RolesNombres && Array.isArray(user.RolesNombres)) {
      return user.RolesNombres;
    }

    // Compatibilidad con formato antiguo
    if (user.RolNombre) {
      return [user.RolNombre];
    }

    return [];
  } catch (error) {
    console.error('Error al obtener nombres de roles:', error);
    return [];
  }
}

/**
 * Verificar si el usuario tiene UN rol específico (por nombre)
 * @param {string} roleName - Nombre del rol a verificar
 * @returns {boolean}
 */
export function hasRole(roleName) {
  const rolesNames = getUserRolesNames();
  return rolesNames.includes(roleName);
}

/**
 * Verificar si el usuario tiene UN rol específico (por ID)
 * @param {number} roleId - ID del rol a verificar
 * @returns {boolean}
 */
export function hasRoleById(roleId) {
  const rolesIds = getUserRolesIds();
  return rolesIds.includes(roleId);
}

/**
 * Verificar si el usuario tiene AL MENOS UNO de los roles especificados
 * @param {Array<string>} roleNames - Array de nombres de roles
 * @returns {boolean}
 */
export function hasAnyRole(roleNames) {
  const userRoles = getUserRolesNames();
  return roleNames.some(role => userRoles.includes(role));
}

/**
 * Verificar si el usuario tiene TODOS los roles especificados
 * @param {Array<string>} roleNames - Array de nombres de roles
 * @returns {boolean}
 */
export function hasAllRoles(roleNames) {
  const userRoles = getUserRolesNames();
  return roleNames.every(role => userRoles.includes(role));
}

/**
 * Verificar si el usuario tiene AL MENOS UNO de los roles por ID
 * @param {Array<number>} roleIds - Array de IDs de roles
 * @returns {boolean}
 */
export function hasAnyRoleById(roleIds) {
  const userRolesIds = getUserRolesIds();
  return roleIds.some(id => userRolesIds.includes(id));
}

/**
 * Verificar si el usuario tiene TODOS los roles por ID
 * @param {Array<number>} roleIds - Array de IDs de roles
 * @returns {boolean}
 */
export function hasAllRolesById(roleIds) {
  const userRolesIds = getUserRolesIds();
  return roleIds.every(id => userRolesIds.includes(id));
}

/**
 * Verificar si el usuario es administrador
 * @returns {boolean}
 */
export function isAdmin() {
  return hasAnyRole(['Super Usuario', 'Director', 'Administrador']);
}

/**
 * Verificar si el usuario puede ver la configuración
 * @returns {boolean}
 */
export function canAccessConfig() {
  return hasAnyRole(['Super Usuario', 'Director', 'Administrador']);
}

/**
 * Obtener el nombre del rol principal (el primero)
 * @returns {string}
 */
export function getPrimaryRoleName() {
  const names = getUserRolesNames();
  return names.length > 0 ? names[0] : 'Sin rol';
}

/**
 * Obtener una cadena con todos los roles separados por coma
 * @returns {string}
 */
export function getRolesDisplayString() {
  const names = getUserRolesNames();
  return names.length > 0 ? names.join(', ') : 'Sin roles asignados';
}

// Exportar todo como default también
export default {
  getUserRoles,
  getUserRolesIds,
  getUserRolesNames,
  hasRole,
  hasRoleById,
  hasAnyRole,
  hasAllRoles,
  hasAnyRoleById,
  hasAllRolesById,
  isAdmin,
  canAccessConfig,
  getPrimaryRoleName,
  getRolesDisplayString
};
