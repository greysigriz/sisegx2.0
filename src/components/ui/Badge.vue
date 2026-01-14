<template>
  <span :class="badgeClass">
    <slot />
  </span>
</template>

<script setup>
import { computed } from 'vue'
import { useAttrs } from 'vue'

defineOptions({ name: 'UiBadge' })

const attrs = useAttrs()

const badgeClass = computed(() => {
  const incoming = attrs.class || ''
  return ['px-2 py-1 rounded font-semibold', incoming].filter(Boolean).join(' ')
})
</script>
