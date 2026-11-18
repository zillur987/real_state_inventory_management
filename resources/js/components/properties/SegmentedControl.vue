<script setup>
const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  options: { type: Array, required: true }, // ['Yes','No'] or [{ value, label }]
  invalid: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  id: { type: String, default: undefined },
})
const emit = defineEmits(['update:modelValue', 'blur'])

const normalized = props.options.map((o) =>
  typeof o === 'object' ? o : { value: o, label: o },
)

function onKey(e, index) {
  if (!['ArrowRight', 'ArrowLeft'].includes(e.key)) return
  e.preventDefault()
  const step = e.key === 'ArrowRight' ? 1 : -1
  const next = normalized[(index + step + normalized.length) % normalized.length]
  emit('update:modelValue', next.value)
  e.currentTarget.parentElement.querySelectorAll('button')[
    (index + step + normalized.length) % normalized.length
  ]?.focus()
}
</script>

<template>
  <div
    :id="id"
    class="seg"
    :class="{ 'is-invalid': invalid, 'is-disabled': disabled }"
    role="radiogroup"
  >
    <button
      v-for="(opt, i) in normalized"
      :key="opt.value"
      type="button"
      role="radio"
      class="seg__btn"
      :class="{ 'is-active': modelValue === opt.value }"
      :aria-checked="modelValue === opt.value"
      :tabindex="modelValue === opt.value || (modelValue === '' && i === 0) ? 0 : -1"
      :disabled="disabled"
      @click="emit('update:modelValue', opt.value)"
      @keydown="onKey($event, i)"
      @blur="emit('blur')"
    >
      {{ opt.label }}
    </button>
  </div>
</template>

<style scoped>
.seg {
  display: flex; gap: 2px; padding: 3px; width: 100%;
  background: #eef1f4; border: 1px solid var(--c-border); border-radius: 10px;
}
.seg.is-invalid { border-color: var(--c-danger); background: var(--c-danger-soft); }
.seg__btn {
  flex: 1; min-height: 34px; padding: 0 10px; border: 0; border-radius: 7px;
  background: transparent; color: var(--c-muted); font: inherit; font-size: 13px; font-weight: 600;
  cursor: pointer; transition: background .15s, color .15s, box-shadow .15s; white-space: nowrap;
}
.seg__btn:hover:not(:disabled):not(.is-active) { color: var(--c-text); }
.seg__btn.is-active { background: var(--c-surface); color: var(--c-primary); box-shadow: 0 1px 2px rgba(16,24,40,.12); }
.seg__btn:focus-visible { outline: 2px solid var(--c-primary); outline-offset: 1px; }
.seg.is-disabled { opacity: .55; }
.seg.is-disabled .seg__btn { cursor: not-allowed; }
</style>