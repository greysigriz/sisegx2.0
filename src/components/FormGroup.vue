<template>
  <div class="form-group" v-motion-fade-visible-once
       :initial="{ opacity: 0, y: 30 }"
       :enter="{ opacity: 1, y: 0, transition: { duration: 800, delay: delay } }">
    <label :for="id">{{ label }}</label>
    <component
      :is="type === 'textarea' ? 'textarea' : type === 'select' ? 'select' : 'input'"
      :id="id"
      v-model="modelProxy"
      :type="type !== 'select' && type !== 'textarea' ? type : undefined"
      :placeholder="placeholder"
      class="form-control"
      :required="required"
    >
      <option v-if="type === 'select'" v-for="option in options" :key="option.value" :value="option.value">
        {{ option.text }}
      </option>
    </component>
    <small v-if="helpText" class="help-text">{{ helpText }}</small>
    <div v-if="error" class="error-message">
      <font-awesome-icon icon="fa-solid fa-exclamation-circle" /> {{ error }}
    </div>
  </div>
</template>

<script>
export default {
  name: 'FormGroup',
  props: {
    id: { type: String, required: true },
    label: { type: String, required: true },
    type: { type: String, default: 'text' },
    model: [String, Number],
    placeholder: { type: String, default: '' },
    error: { type: String, default: '' },
    required: { type: Boolean, default: false },
    delay: { type: Number, default: 0 },
    helpText: { type: String, default: '' },
    options: { type: Array, default: () => [] }
  },
  emits: ['update:model'],
  computed: {
    modelProxy: {
      get() {
        return this.model;
      },
      set(value) {
        this.$emit('update:model', value);
      }
    }
  }
}
</script>

<style scoped>
.form-group {
  margin-bottom: 1rem;
}
.error-message {
  color: #ff0000;
  font-size: 0.9rem;
  margin-top: 0.25rem;
}
.help-text {
  font-size: 0.85rem;
  color: #666;
}
</style>
