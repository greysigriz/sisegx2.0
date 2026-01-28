/**
 * useVisibilityReflow.js
 *
 * PROPÓSITO: Corregir bug intermitente de layout cuando cambias de pestañas.
 *
 * PROBLEMA: Cuando una pestaña se oculta, ECharts y Bootstrap quedan con
 * clientWidth=0. Al volver, nadie dispara resize() porque el navegador
 * NO emite 'resize' al cambiar de pestaña.
 *
 * SOLUCIÓN: Escuchar document.visibilitychange y forzar reflow cuando vuelve.
 *
 * SEGURIDAD: Si falla, la app sigue funcionando igual que antes.
 */

import { onMounted, onUnmounted } from 'vue'

export function useVisibilityReflow() {
  const handleVisibilityChange = () => {
    // Solo actuar cuando la pestaña vuelve visible
    if (!document.hidden) {
      // Disparar resize para que gráficos y grid se recalculen
      window.dispatchEvent(new Event('resize'))
    }
  }

  onMounted(() => {
    document.addEventListener('visibilitychange', handleVisibilityChange)
  })

  onUnmounted(() => {
    document.removeEventListener('visibilitychange', handleVisibilityChange)
  })

  return { handleVisibilityChange }
}
