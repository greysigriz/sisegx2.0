<template>
  <div class="toast-stack">
    <TransitionGroup name="toast-slide">
      <div
        v-for="t in toasts"
        :key="t.id"
        class="toast-item"
        :class="'toast-item--' + t.type"
        @click="$emit('dismiss', t.id)"
      >
        <i :class="iconClass(t.type)" class="toast-icon"></i>
        <span class="toast-msg">{{ t.message }}</span>
        <button class="toast-close" @click.stop="$emit('dismiss', t.id)">&times;</button>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
defineProps({
  toasts: { type: Array, default: () => [] }
})
defineEmits(['dismiss'])

function iconClass(type) {
  const map = {
    success: 'fas fa-check-circle',
    error: 'fas fa-exclamation-circle',
    warning: 'fas fa-exclamation-triangle',
    info: 'fas fa-info-circle'
  }
  return map[type] || map.info
}
</script>

<style>
.toast-stack {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 99999;
  display: flex;
  flex-direction: column;
  gap: 8px;
  pointer-events: none;
  max-width: 400px;
  width: calc(100vw - 40px);
}

.toast-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  border-radius: 8px;
  color: #fff;
  font-size: 14px;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  pointer-events: auto;
  cursor: pointer;
  backdrop-filter: blur(8px);
}

.toast-item--success {
  background: linear-gradient(135deg, #2ecc40, #27ae60);
}

.toast-item--error {
  background: linear-gradient(135deg, #e74c3c, #c0392b);
}

.toast-item--warning {
  background: linear-gradient(135deg, #f39c12, #e67e22);
}

.toast-item--info {
  background: linear-gradient(135deg, #165CB1, #1976d2);
}

.toast-icon {
  font-size: 18px;
  flex-shrink: 0;
}

.toast-msg {
  flex: 1;
  line-height: 1.4;
}

.toast-close {
  background: none;
  border: none;
  color: rgba(255, 255, 255, 0.8);
  font-size: 20px;
  cursor: pointer;
  padding: 0 2px;
  line-height: 1;
  flex-shrink: 0;
}

.toast-close:hover {
  color: #fff;
}

.toast-slide-enter-active {
  transition: all 0.3s ease-out;
}

.toast-slide-leave-active {
  transition: all 0.2s ease-in;
}

.toast-slide-enter-from {
  opacity: 0;
  transform: translateX(60px);
}

.toast-slide-leave-to {
  opacity: 0;
  transform: translateX(60px) scale(0.95);
}

.toast-slide-move {
  transition: transform 0.3s ease;
}

@media (max-width: 480px) {
  .toast-stack {
    top: 10px;
    right: 10px;
    left: 10px;
    max-width: none;
    width: auto;
  }
}
</style>
