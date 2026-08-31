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
  weather_date: '',
  rainfall_mm: null as number | null,
  wind_speed: null as number | null,
  temperature: null as number | null,
});

async function save() {
  saving.value = true
  try {
    await apiFetch('/v1/weather', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form),
      parseJson: true,
    })
    toast.add({ title: 'Weather record created', description: 'The record was saved successfully.', color: 'success' })
    router.push('/cms/weather')
  } catch (e: any) {
    toast.add({ title: 'Failed to create weather record', description: e.message ?? 'Please try again.', color: 'error' })
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-5">Add Weather Record</h1>

    <UForm :state="form" @submit="save">
      <UFormField label="Date">
        <UInput v-model="form.weather_date" type="date" />
      </UFormField>

      <UFormField label="Rainfall (mm)">
        <UInput v-model="form.rainfall_mm" type="number" step="0.1" />
      </UFormField>

      <UFormField label="Wind Speed (km/h)">
        <UInput v-model="form.wind_speed" type="number" step="0.1" />
      </UFormField>

      <UFormField label="Temperature (°C)">
        <UInput v-model="form.temperature" type="number" step="0.1" />
      </UFormField>

      <UButton type="submit" :loading="saving"> Save </UButton>
    </UForm>
  </div>
</template>
