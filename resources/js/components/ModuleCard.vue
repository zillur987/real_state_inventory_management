<script setup>
defineProps({
    module: { type: Object, required: true },
    variant: { type: String, default: 'default' }, // default | wide
});
</script>

<template>
    <component
        :is="module.route ? 'RouterLink' : 'div'"
        :to="module.route ? { name: module.route } : undefined"
        class="flex flex-col items-center justify-center rounded-xl px-6 py-8 text-center transition"
        :class="[
            variant === 'wide' ? 'bg-mint-100' : 'bg-brand-100',
            module.route ? 'cursor-pointer hover:shadow-md' : 'cursor-default',
        ]"
    >
        <component :is="module.icon" class="mb-3 size-5 text-slate-800" />
        <h3 class="text-base font-semibold text-slate-900">{{ module.title }}</h3>
        <p class="mt-1 text-sm text-slate-500">{{ module.subtitle }}</p>
        <span v-if="!module.route" class="mt-2 text-xs text-slate-400">Coming soon</span>
    </component>
</template>
