<script setup>
import { computed, onBeforeUnmount, ref } from 'vue'

/**
 * v-model: Array of File objects and/or existing server items { id, name, url, size }.
 */
const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  kind: { type: String, default: 'image' }, // 'image' | 'document'
  maxFiles: { type: Number, default: 10 },
  maxSizeMb: { type: Number, default: 5 },
  invalid: { type: Boolean, default: false },
  id: { type: String, default: undefined },
})
const emit = defineEmits(['update:modelValue', 'blur'])

const MIME = {
  image: ['image/jpeg', 'image/png', 'image/webp'],
  document: [
    'application/pdf', 'image/jpeg', 'image/png', 'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  ],
}
const LABEL = { image: 'JPG, PNG or WEBP', document: 'PDF, DOC, DOCX, JPG or PNG' }

const inputRef = ref(null)
const dragging = ref(false)
const problems = ref([])
const urls = new Map()

const isImage = computed(() => props.kind === 'image')
const accept = computed(() => MIME[props.kind].join(','))
const canAdd = computed(() => props.modelValue.length < props.maxFiles)

function urlFor(file) {
  if (!(file instanceof File)) return file.url
  if (!urls.has(file)) urls.set(file, URL.createObjectURL(file))
  return urls.get(file)
}

function size(bytes = 0) {
  if (bytes < 1024 * 1024) return `${Math.max(1, Math.round(bytes / 1024))} KB`
  return `${(bytes / 1024 / 1024).toFixed(1)} MB`
}

function ext(name = '') {
  return (name.split('.').pop() || 'file').slice(0, 4).toUpperCase()
}

function addFiles(fileList) {
  const next = [...props.modelValue]
  const issues = []

  for (const file of Array.from(fileList)) {
    if (next.length >= props.maxFiles) {
      issues.push(`Only ${props.maxFiles} files are allowed.`)
      break
    }
    if (!MIME[props.kind].includes(file.type)) {
      issues.push(`${file.name}: unsupported format. Use ${LABEL[props.kind]}.`)
      continue
    }
    if (file.size > props.maxSizeMb * 1024 * 1024) {
      issues.push(`${file.name}: larger than ${props.maxSizeMb} MB.`)
      continue
    }
    const duplicate = next.some((f) => f.name === file.name && f.size === file.size)
    if (duplicate) continue
    next.push(file)
  }

  problems.value = issues
  emit('update:modelValue', next)
  emit('blur')
}

function onPick(e) {
  addFiles(e.target.files)
  e.target.value = ''
}

function onDrop(e) {
  dragging.value = false
  addFiles(e.dataTransfer.files)
}

function remove(index) {
  const file = props.modelValue[index]
  if (urls.has(file)) {
    URL.revokeObjectURL(urls.get(file))
    urls.delete(file)
  }
  emit('update:modelValue', props.modelValue.filter((_, i) => i !== index))
  emit('blur')
}

onBeforeUnmount(() => urls.forEach((u) => URL.revokeObjectURL(u)))
</script>

<template>
  <div :id="id" class="up">
    <div
      v-if="canAdd"
      class="up__drop"
      :class="{ 'is-drag': dragging, 'is-invalid': invalid }"
      tabindex="0"
      role="button"
      :aria-label="isImage ? 'Upload property photos' : 'Upload property documents'"
      @click="inputRef.click()"
      @keydown.enter.prevent="inputRef.click()"
      @keydown.space.prevent="inputRef.click()"
      @dragover.prevent="dragging = true"
      @dragleave.prevent="dragging = false"
      @drop.prevent="onDrop"
    >
      <span class="up__icon" aria-hidden="true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <path v-if="isImage" d="M4 16l4.6-4.6a2 2 0 0 1 2.8 0L16 16m-2-2l1.6-1.6a2 2 0 0 1 2.8 0L20 14M14 8h.01M6 20h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z" />
          <path v-else d="M12 3v12m0-12l-4 4m4-4l4 4M5 21h14" />
        </svg>
      </span>
      <div class="up__text">
        <strong>{{ isImage ? 'Drop photos here or browse' : 'Drop documents here or browse' }}</strong>
        <span>{{ LABEL[kind] }} · up to {{ maxSizeMb }} MB each · {{ modelValue.length }}/{{ maxFiles }} added</span>
      </div>
      <input ref="inputRef" type="file" multiple hidden :accept="accept" @change="onPick" />
    </div>

    <ul v-if="problems.length" class="up__problems" role="alert">
      <li v-for="p in problems" :key="p">{{ p }}</li>
    </ul>

    <ul v-if="modelValue.length && isImage" class="up__grid">
      <li v-for="(file, i) in modelValue" :key="file.id ?? `${file.name}-${file.size}`" class="up__thumb">
        <img :src="urlFor(file)" :alt="file.name" loading="lazy" />
        <span v-if="i === 0" class="up__cover">Cover</span>
        <button type="button" class="up__remove" :aria-label="`Remove ${file.name}`" @click="remove(i)">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18" /></svg>
        </button>
      </li>
    </ul>

    <ul v-else-if="modelValue.length" class="up__list">
      <li v-for="(file, i) in modelValue" :key="file.id ?? `${file.name}-${file.size}`" class="up__row">
        <span class="up__ext">{{ ext(file.name) }}</span>
        <span class="up__name">{{ file.name }}</span>
        <span class="up__size">{{ size(file.size) }}</span>
        <button type="button" class="up__remove up__remove--row" :aria-label="`Remove ${file.name}`" @click="remove(i)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18" /></svg>
        </button>
      </li>
    </ul>
  </div>
</template>

<style scoped>
.up { display: flex; flex-direction: column; gap: 10px; }
.up__drop {
  display: flex; align-items: center; gap: 14px; padding: 16px 18px; cursor: pointer;
  border: 1.5px dashed var(--c-border-strong); border-radius: 12px; background: #fafbfc;
  transition: border-color .15s, background .15s;
}
.up__drop:hover, .up__drop.is-drag { border-color: var(--c-primary); background: var(--c-primary-soft); }
.up__drop.is-invalid { border-color: var(--c-danger); background: var(--c-danger-soft); }
.up__drop:focus-visible { outline: 2px solid var(--c-primary); outline-offset: 2px; }
.up__icon {
  display: grid; place-items: center; width: 42px; height: 42px; flex: none;
  border-radius: 10px; background: var(--c-surface); color: var(--c-primary);
  border: 1px solid var(--c-border);
}
.up__text { display: flex; flex-direction: column; gap: 2px; font-size: 12.5px; color: var(--c-muted); }
.up__text strong { color: var(--c-text); font-size: 13.5px; }
.up__problems { margin: 0; padding: 0; list-style: none; font-size: 12px; color: var(--c-danger); display: grid; gap: 2px; }

.up__grid { margin: 0; padding: 0; list-style: none; display: grid; grid-template-columns: repeat(auto-fill, minmax(112px, 1fr)); gap: 10px; }
.up__thumb { position: relative; aspect-ratio: 4 / 3; border-radius: 10px; overflow: hidden; border: 1px solid var(--c-border); background: #eef1f4; }
.up__thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.up__cover {
  position: absolute; left: 6px; bottom: 6px; font-size: 11px; font-weight: 600;
  background: rgba(15, 23, 32, .78); color: #fff; padding: 2px 8px; border-radius: 999px;
}
.up__remove {
  position: absolute; top: 6px; right: 6px; width: 22px; height: 22px; display: grid; place-items: center;
  border: 0; border-radius: 50%; background: rgba(15, 23, 32, .78); color: #fff; cursor: pointer;
}
.up__remove:hover { background: var(--c-danger); }

.up__list { margin: 0; padding: 0; list-style: none; border: 1px solid var(--c-border); border-radius: 10px; overflow: hidden; }
.up__row { display: flex; align-items: center; gap: 12px; padding: 9px 12px; font-size: 13px; background: var(--c-surface); }
.up__row + .up__row { border-top: 1px solid var(--c-border); }
.up__ext { flex: none; width: 40px; text-align: center; font-size: 10.5px; font-weight: 700; color: var(--c-primary); background: var(--c-primary-soft); border-radius: 6px; padding: 4px 0; }
.up__name { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.up__size { color: var(--c-muted); font-size: 12px; }
.up__remove--row { position: static; background: transparent; color: var(--c-muted); }
.up__remove--row:hover { background: var(--c-danger-soft); color: var(--c-danger); }
</style>