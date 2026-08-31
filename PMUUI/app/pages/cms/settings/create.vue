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

const form = reactive({
  key: '',
  value: '',
  type: 'string',
  description: '',
});

async function save() {
  saving.value = true
  try {
    await apiFetch('/v1/settings', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form),
      parseJson: true,
    })
    toast.add({ title: 'Setting created', description: 'The setting was saved successfully.', color: 'success' })
    router.push('/cms/settings')
  } catch (e: any) {
    toast.add({ title: 'Failed to create setting', description: e.message ?? 'Please try again.', color: 'error' })
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-5">Add Setting</h1>

    <UForm :state="form" @submit="save">
      <UFormField label="Key">
        <UInput v-model="form.key" />
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

      <UButton type="submit" :loading="saving"> Save </UButton>
    </UForm>
  </div>
</template>
