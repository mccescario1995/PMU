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
  weather_date: '',
  rainfall_mm: null as number | null,
  wind_speed: null as number | null,
  temperature: null as number | null,
})

onMounted(async () => {
  const item = ((await apiFetch('/v1/weather/' + id, { parseJson: true })) as any).data
  Object.assign(form, {
    weather_date: item.weather_date?.slice(0, 10) ?? '',
    rainfall_mm: item.rainfall_mm,
    wind_speed: item.wind_speed,
    temperature: item.temperature,
  })
})

async function save() {
  saving.value = true
  try {
    await apiFetch('/v1/weather/' + id, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form),
      parseJson: true,
    })
    toast.add({ title: 'Weather record updated', description: 'The record was saved successfully.', color: 'success' })
    router.push('/cms/weather')
  } catch (e: any) {
    toast.add({ title: 'Failed to update weather record', description: e.message ?? 'Please try again.', color: 'error' })
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-5">Edit Weather Record #{{ id }}</h1>

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

      <div class="flex gap-2 mt-4">
        <UButton type="submit" :loading="saving"> Save </UButton>
        <UButton variant="ghost" to="/cms/weather"> Cancel </UButton>
      </div>
    </UForm>
  </div>
</template>
