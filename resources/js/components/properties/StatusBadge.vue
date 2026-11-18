<script setup>
import { computed } from 'vue'

const props = defineProps({ value: { type: String, default: '' } })

const TONES = {
  Available: 'green', Verified: 'green', Registered: 'green',
  Reserved: 'amber', Pending: 'amber', 'On process': 'amber', 'Semi-ready': 'amber',
  Sold: 'slate', Used: 'slate',
  Withdrawn: 'red', 'Non-verified': 'red', 'Non-registered': 'red',
  New: 'blue', 'Under-construction': 'violet',
}
const tone = computed(() => TONES[props.value] ?? 'slate')
</script>

<template>
  <span v-if="value" class="badge" :class="`badge--${tone}`">
    <i class="badge__dot" />{{ value }}
  </span>
  <span v-else class="badge-empty">—</span>
</template>

<style scoped>
.badge {
  display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px 3px 8px;
  border-radius: 999px; font-size: 12px; font-weight: 600; line-height: 1.5; white-space: nowrap;
}
.badge__dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.badge--green { background: #e6f6ee; color: #0b7a43; }
.badge--amber { background: #fff4dc; color: #9a6200; }
.badge--red { background: #fdecec; color: #b42318; }
.badge--blue { background: #e7f0fe; color: #1d4fb8; }
.badge--violet { background: #f0eafe; color: #5b3fb5; }
.badge--slate { background: #eceff3; color: #475467; }
.badge-empty { color: #98a2b3; }
</style>