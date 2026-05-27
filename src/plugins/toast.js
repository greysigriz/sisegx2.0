import { createApp, ref, h } from 'vue'
import ToastContainer from '@/components/ui/ToastContainer.vue'

const toasts = ref([])
let toastId = 0

function addToast(type, message, duration = 4000) {
  const id = ++toastId
  toasts.value.push({ id, type, message })
  if (toasts.value.length > 3) {
    toasts.value.shift()
  }
  setTimeout(() => {
    toasts.value = toasts.value.filter(t => t.id !== id)
  }, duration)
}

const toast = {
  success: (msg) => addToast('success', msg),
  error: (msg) => addToast('error', msg, 6000),
  warning: (msg) => addToast('warning', msg, 5000),
  info: (msg) => addToast('info', msg)
}

export default {
  install(app) {
    app.config.globalProperties.$toast = toast
    window.$toast = toast

    const container = document.createElement('div')
    container.id = 'toast-container-root'
    document.body.appendChild(container)

    const toastApp = createApp({
      setup() {
        return () => h(ToastContainer, { toasts: toasts.value, onDismiss: (id) => {
          toasts.value = toasts.value.filter(t => t.id !== id)
        }})
      }
    })
    toastApp.mount(container)
  }
}
