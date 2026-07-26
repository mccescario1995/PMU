<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted } from 'vue'

definePageMeta({
  layout: "dashboard",
});

const weather = ref<any[]>([])
const loading = ref(true)
const showForm = ref(false)
const editing = ref<any>(null)

const form = reactive({
  weather_date: new Date().toISOString().slice(0, 10),
  rainfall_mm: null,
  wind_speed: null,
  temperature: null,
})

function reset() {
  form.weather_date = new Date().toISOString().slice(0, 10)
  form.rainfall_mm = null
  form.wind_speed = null
  form.temperature = null
  editing.value = null
  showForm.value = false
}

function startEdit(item: any) {
  editing.value = item
  form.weather_date = item.weather_date
  form.rainfall_mm = item.rainfall_mm
  form.wind_speed = item.wind_speed
  form.temperature = item.temperature
  showForm.value = true
}

async function submit() {
  const payload = {
    weather_date: form.weather_date,
    rainfall_mm: form.rainfall_mm ?? null,
    wind_speed: form.wind_speed ?? null,
    temperature: form.temperature ?? null,
  }

  if (editing.value) {
    await apiFetch(`/v1/weather/${editing.value.id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
      parseJson: true,
    })
  } else {
    await apiFetch('/v1/weather', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
      parseJson: true,
    })
  }
  reset()
  await load()
}

async function remove(item: any) {
  if (!confirm('Delete this record?')) return
  await apiFetch(`/v1/weather/${item.id}`, { method: 'DELETE' })
  await load()
}

async function load() {
  loading.value = true
  weather.value = (await apiFetch('/v1/weather', { parseJson: true })) as any[]
  loading.value = false
}

onMounted(load)
</script>

<template>
  <div class="p-6 space-y-5">
    <div class="flex justify-between">
      <div>
        <h1 class="text-2xl font-bold">Weather Data</h1>
        <p class="text-slate-500">Log weather conditions affecting port operations.</p>
      </div>
      <UButton icon="i-lucide-plus" @click="showForm = true; reset()"> Add Record </UButton>
    </div>

    <UCard v-if="showForm">
      <template #header> {{ editing ? 'Edit' : 'New' }} Weather Record </template>
      <UForm @submit="submit">
        <UFormField label="Date" required>
          <UInput type="date" v-model="form.weather_date" />
        </UFormField>
        <UFormField label="Rainfall (mm)">
          <UInput type="number" v-model.number="form.rainfall_mm" />
        </UFormField>
        <UFormField label="Wind Speed (km/h)">
          <UInput type="number" v-model.number="form.wind_speed" />
        </UFormField>
        <UFormField label="Temperature (°C)">
          <UInput type="number" v-model.number="form.temperature" />
        </UFormField>
        <div class="flex gap-2 mt-4">
          <UButton type="submit"> Save </UButton>
          <UButton variant="ghost" @click="reset"> Cancel </UButton>
        </div>
      </UForm>
    </UCard>

    <UTable :data="weather" :columns="[
      { accessorKey: 'id', header: '#', cell: ({ row }) => `#${row.getValue('id')}` },
      { accessorKey: 'weather_date', header: 'Date' },
      { accessorKey: 'rainfall_mm', header: 'Rainfall (mm)', meta: { class: { td: 'text-right font-mono' } } },
      { accessorKey: 'wind_speed', header: 'Wind Speed', meta: { class: { td: 'text-right font-mono' } } },
      { accessorKey: 'temperature', header: 'Temp (°C)', meta: { class: { td: 'text-right font-mono' } } },
      { accessorKey: 'action', header: 'Action' },
    ]" :loading="loading">
      <template #action-cell="{ row }">
        <div class="flex gap-1">
          <UButton size="xs" variant="outline" @click="startEdit(row.original)"> Edit </UButton>
          <UButton size="xs" color="error" variant="ghost" @click="remove(row.original)"> Delete </UButton>
        </div>
      </template>
    </UTable>
  </div>
</template>
