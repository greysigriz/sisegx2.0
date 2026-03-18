// src/composables/useCountUp.js
// Animacion count-up para numeros
import { ref, watch, onUnmounted } from 'vue'

export function useCountUp(targetRef, duration = 600) {
  const display = ref(0)
  let animFrame = null

  function animate(from, to) {
    if (animFrame) cancelAnimationFrame(animFrame)
    const start = performance.now()
    const diff = to - from

    function step(now) {
      const elapsed = now - start
      const progress = Math.min(elapsed / duration, 1)
      // ease-out quad
      const eased = 1 - (1 - progress) * (1 - progress)
      display.value = Math.round(from + diff * eased)

      if (progress < 1) {
        animFrame = requestAnimationFrame(step)
      } else {
        display.value = to
      }
    }

    animFrame = requestAnimationFrame(step)
  }

  watch(targetRef, (newVal, oldVal) => {
    const n = Number(newVal) || 0
    const o = Number(oldVal) || 0
    if (n !== o) animate(o, n)
    else display.value = n
  }, { immediate: true })

  onUnmounted(() => {
    if (animFrame) cancelAnimationFrame(animFrame)
  })

  return display
}
