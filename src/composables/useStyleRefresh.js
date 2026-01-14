// src/composables/useStyleRefresh.js
import { onMounted, nextTick } from 'vue'

export function useStyleRefresh() {
  const forceStyleRefresh = async () => {
    await nextTick()

    // Forzar que el navegador recalcule todos los estilos
    const root = document.documentElement
    root.style.display = 'none'
    root.offsetHeight // Trigger reflow
    root.style.display = ''

    // Segunda pasada para asegurar
    await nextTick()
    document.body.offsetHeight
  }

  onMounted(async () => {
    await forceStyleRefresh()

    // Repetir después de un tick adicional por si acaso
    setTimeout(async () => {
      await forceStyleRefresh()
    }, 50)
  })

  return {
    forceStyleRefresh
  }
}
