export function usePeticionUtils() {
  const getImpLabel = (n) => ({ 1: 'Critica', 2: 'Alta', 3: 'Media', 4: 'Baja' }[n] || 'Baja')

  const getImpClass = (n) => ({ 1: 'bv-imp--critica', 2: 'bv-imp--alta', 3: 'bv-imp--media' }[n] || 'bv-imp--baja')

  const getEstadoBadgeClass = (e) => {
    const map = {
      'Sin revisar': 'bv-est--pendiente', 'Pendiente': 'bv-est--pendiente',
      'Esperando recepción': 'bv-est--pendiente', 'Esperando recepcion': 'bv-est--pendiente',
      'Aceptada en proceso': 'bv-est--proceso', 'Aceptado en proceso': 'bv-est--proceso',
      'Completado': 'bv-est--completado', 'Completada': 'bv-est--completado',
      'Devuelto a seguimiento': 'bv-est--devuelto',
      'Rechazado': 'bv-est--cerrado', 'Cancelada': 'bv-est--cerrado', 'Improcedente': 'bv-est--cerrado'
    }
    return map[e] || ''
  }

  const getEstadoIcon = (estado) => {
    const map = {
      'Sin revisar': 'fas fa-file-alt',
      'Pendiente': 'fas fa-hourglass-half',
      'Esperando recepcion': 'fas fa-clock',
      'Esperando recepción': 'fas fa-clock',
      'Aceptado en proceso': 'fas fa-cog',
      'Aceptada en proceso': 'fas fa-cog',
      'Devuelto a seguimiento': 'fas fa-undo',
      'Rechazado': 'fas fa-times-circle',
      'Completado': 'fas fa-check-circle',
      'Completada': 'fas fa-check-circle',
      'Cancelada': 'fas fa-ban',
      'Improcedente': 'fas fa-minus-circle'
    }
    return map[estado] || 'fas fa-file-alt'
  }

  const getNivelLabel = (nivel) => {
    return { 1: 'Muy Alta', 2: 'Alta', 3: 'Media', 4: 'Baja', 5: 'Muy Baja' }[nivel] || 'N/D'
  }

  const truncateText = (str, len) => {
    if (!str) return '-'
    return str.length > len ? str.substring(0, len) + '...' : str
  }

  const formatFullDate = (d) => {
    if (!d) return '-'
    return new Date(d).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' })
  }

  const formatShortDate = (dateString) => {
    const date = new Date(dateString)
    return ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'][date.getDay()]
  }

  return {
    getImpLabel, getImpClass,
    getEstadoBadgeClass, getEstadoIcon, getNivelLabel,
    truncateText, formatFullDate, formatShortDate
  }
}
