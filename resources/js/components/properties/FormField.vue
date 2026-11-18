<script setup>
import { computed } from 'vue'

const props = defineProps({
  label: { type: String, required: true },
  forId: { type: String, default: '' },
  required: { type: Boolean, default: false },
  error: { type: String, default: '' },
  hint: { type: String, default: '' },
  locked: { type: String, default: '' },
  span: { type: Number, default: 1 },
})

const spanClass = computed(() => (props.span > 1 ? `field--span-${Math.min(props.span, 3)}` : ''))
</script>

<template>
  <div class="field" :class="spanClass">
    <div class="field__head">
      <label class="field__label" :for="forId || undefined">
        {{ label }}<span v-if="required" class="field__req" aria-hidden="true"> *</span>
      </label>
      <span v-if="locked" class="field__lock">{{ locked }}</span>
    </div>

    <slot />

    <p v-if="error" class="field__error" role="alert">{{ error }}</p>
    <p v-else-if="hint" class="field__hint">{{ hint }}</p>
  </div>
</template>

<style scoped>
.field { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
.field--span-2 { grid-column: span 2; }
.field--span-3 { grid-column: 1 / -1; }

.field__head { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.field__label { font-size: 13px; font-weight: 600; color: #17212b; }
.field__req { color: #dc2626; font-weight: 700; }
.field__lock {
  padding: 1px 8px; border-radius: 999px; background: #fff1cf; color: #8a5a00;
  font-size: 11px; font-weight: 600; white-space: nowrap;
}
.field__hint { margin: 0; font-size: 12px; color: #5f6b7a; }
.field__error { margin: 0; font-size: 12px; font-weight: 600; color: #dc2626; }

@media (max-width: 1100px) {
  .field--span-3 { grid-column: 1 / -1; }
}
@media (max-width: 900px) {
  .field--span-2, .field--span-3 { grid-column: auto; }
}
</style>