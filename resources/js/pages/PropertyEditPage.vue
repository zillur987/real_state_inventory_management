<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLayout from '@/layouts/AppLayout.vue'
import PropertyForm from '@/components/properties/PropertyForm.vue'
import { propertyApi } from '@/api/propertyApi'
import { formFromApi, mapServerErrors, toApi } from '@/utils/propertyMapper'
import { useToast } from 'vue-toastification';

const route = useRoute()
const router = useRouter()
const formRef = ref(null)
const toast = useToast()

const initial = ref(null)
const loading = ref(true)
const saving = ref(false)
const loadError = ref('')
const apiErrors = ref({})

// Same locks as the create page; see the note there.
const permissions = { editAsking: true, setFinalPrice: false, viewManagement: false, viewOwner: false }

const serverErrors = computed(() => mapServerErrors(apiErrors.value))
const goDetails = () => router.push({ name: 'properties.show', params: { id: route.params.id } })

async function load() {
  loading.value = true
  loadError.value = ''
  try {
    initial.value = formFromApi(await propertyApi.show(route.params.id))
  } catch (e) {
    loadError.value = e.response?.status === 404
      ? 'This property could not be found. It may have been removed.'
      : 'Unable to load this property. Please try again.'
    toast.error(loadError.value, { title: 'Could not load property' })
  } finally {
    loading.value = false
  }
}

async function onSubmit(payload) {
  saving.value = true
  apiErrors.value = {}
  try {
    await propertyApi.update(route.params.id, toApi(payload, { keepNulls: true }))
    formRef.value?.markClean()
    toast.success('Your changes have been saved.', { title: 'Property updated' })
    await goDetails()
  } catch (e) {
    apiErrors.value = e.response?.data?.errors ?? { general: ['Unable to save the changes. Please try again.'] }
    if (e.response?.status === 422) toast.error('Please fix the highlighted fields and try again.', { title: 'Check the form' })
    else toast.error(apiErrors.value.general?.[0] ?? 'Unable to save the changes.', { title: 'Save failed' })
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <AppLayout>
    <div v-if="loading" class="p-8 text-sm text-slate-500">Loading property…</div>

    <div v-else-if="loadError" class="rounded-md bg-red-50 p-4 text-sm text-red-700" role="alert">
      {{ loadError }}
      <button type="button" class="ml-2 font-semibold underline" @click="load">Retry</button>
      <button type="button" class="ml-2 font-semibold underline" @click="router.push({ name: 'properties.index' })">Back to inventory</button>
    </div>

    <template v-else>
      <p v-if="apiErrors.general" class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700" role="alert">
        {{ apiErrors.general[0] }}
      </p>

      <PropertyForm
        ref="formRef"
        mode="edit"
        :initial-values="initial"
        :saving="saving"
        :permissions="permissions"
        :server-errors="serverErrors"
        :require-photos="false"
        @submit="onSubmit"
        @invalid="(count) => toast.warning(`${count} ${count === 1 ? 'field needs' : 'fields need'} your attention.`, { title: 'Check the form' })"
        @cancel="goDetails"
      />
    </template>
  </AppLayout>
</template>