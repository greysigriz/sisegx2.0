<template>
  <button v-bind="attrsWithoutClass" :class="buttonClass">
    <slot />
  </button>
</template>

<script setup>
import { computed } from 'vue'
import { useAttrs } from 'vue'

defineOptions({ name: 'UiButton' })

const props = defineProps({
  size: { type: String, default: '' },
  variant: { type: String, default: '' }
})

const attrs = useAttrs()

const attrsWithoutClass = computed(() => {
  const rest = { ...attrs }
  delete rest.class
  return rest
})

const buttonClass = computed(() => {
  const sizeCls = props.size === 'sm' ? 'text-sm px-2 py-1' : 'px-3 py-2'
  const variantCls = props.variant === 'destructive' ? 'bg-red-600 hover:bg-red-700 text-white' : ''
  const incoming = attrs.class || ''
  return [sizeCls, variantCls, 'rounded', incoming].filter(Boolean).join(' ')
})
</script>
