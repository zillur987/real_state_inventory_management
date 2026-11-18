<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '@/layouts/AppLayout.vue'
import PropertyForm from '@/components/properties/PropertyForm.vue'
import { usePropertiesStore } from '@/stores/properties'
import { mapServerErrors, toApi } from '@/utils/propertyMapper'
import { useToast } from 'vue-toastification';

const router = useRouter()
const properties = usePropertiesStore()
const formRef = ref(null)
const saving = ref(false)
const toast = useToast()

/**
 * Only sections the API can store today are unlocked. Flip these to true once the
 * backend has the owner / commission / final-price columns and endpoints.
 */
const permissions = { editAsking: true, setFinalPrice: false, viewManagement: false, viewOwner: false }

const serverErrors = computed(() => mapServerErrors(properties.errors))

onMounted(() => { properties.errors = {} })

async function onSubmit(payload) {
  saving.value = true
  try {
    await properties.create(toApi(payload))
    formRef.value?.markClean()
    toast.success('The property was added to your inventory.', { title: 'Property created' })
    await router.push({ name: 'properties.index' })
  } catch (e) {
    // The store keeps the validation errors; the form shows them next to each field.
    if (e?.response?.status === 422) toast.error('Please fix the highlighted fields and try again.', { title: 'Check the form' })
    else toast.error(properties.errors.general?.[0] ?? 'Unable to save the property. Please try again.', { title: 'Save failed' })
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <AppLayout>
    <p v-if="properties.errors.general" class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700" role="alert">
      {{ properties.errors.general[0] }}
    </p>

    <PropertyForm
      ref="formRef"
      :saving="saving"
      :permissions="permissions"
      :server-errors="serverErrors"
      :require-photos="false"
      @submit="onSubmit"
      @invalid="(count) => toast.warning(`${count} ${count === 1 ? 'field needs' : 'fields need'} your attention.`, { title: 'Check the form' })"
      @cancel="router.push({ name: 'properties.index' })"
    />
  </AppLayout>
</template>