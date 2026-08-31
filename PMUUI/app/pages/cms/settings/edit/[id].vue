<script setup lang="ts">
import { apiFetch } from '~/composables/useApiFetch'
import { useRouter } from 'vue-router'
import { useToast } from '#imports'
import { ref } from 'vue'

definePageMeta({
  layout: "dashboard",
});

const router = useRouter()
const toast = useToast()
const saving = ref(false)

const route = useRoute()
const id = route.params.id

const form = reactive({
  key: '',
  value: '',
  type: 'string',
  description: '',
})

onMounted(async () => {
  const item = ((await apiFetch('/v1/settings/' + id, { parseJson: true })) as any).data
  Object.assign(form, {
    key: item.key,
    value: item.value ?? '',
    type: item.type ?? 'string',
    description: item.description ?? '',
  })
})

async function save() {
  saving.value = true
  try {
    await apiFetch('/v1/settings/' + id, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form),
      parseJson: true,
    })
    toast.add({ title: 'Setting updated', description: 'The setting was saved successfully.', color: 'success' })
    router.push('/cms/settings')
  } catch (e: any) {
    toast.add({ title: 'Failed to update setting', description: e.message ?? 'Please try again.', color: 'error' })
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-5">Edit Setting #{{ id }}</h1>

    <UForm :state="form" @submit="save">
      <UFormField label="Key">
        <UInput v-model="form.key" :disabled="true" />
      </UFormField>

      <UFormField label="Value">
        <UInput v-model="form.value" />
      </UFormField>

      <UFormField label="Type">
        <USelect v-model="form.type" :items="['string', 'number', 'boolean', 'json']" />
      </UFormField>

      <UFormField label="Description">
        <UTextarea v-model="form.description" />
      </UFormField>

      <div class="flex gap-2 mt-4">
        <UButton type="submit" :loading="saving"> Save </UButton>
        <UButton variant="ghost" to="/cms/settings"> Cancel </UButton>
      </div>
    </UForm>
  </div>
</template>
