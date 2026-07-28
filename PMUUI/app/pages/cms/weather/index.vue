<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted, h } from 'vue'
import { ref } from 'vue'
import { usePermissions } from "~/composables/usePermissions";

definePageMeta({
  layout: "dashboard",
});

const { can } = usePermissions();

const weather = ref<any[]>([])
const loading = ref(true)
const showForm = ref(false)
const editing = ref<any>(null)

const form = reactive({
  weather_date: '',
  rainfall_mm: null as number | null,
  wind_speed: null as number | null,
  temperature: null as number | null,
})

onMounted(async () => {
  loading.value = true
  weather.value = ((await apiFetch('/v1/weather', { parseJson: true })) as any).data
  loading.value = false
})

function openCreate() {
  editing.value = null
  form.weather_date = new Date().toISOString().slice(0, 10)
  form.rainfall_mm = null
  form.wind_speed = null
  form.temperature = null
  showForm.value = true
}

function openEdit(row: any) {
  editing.value = row
  form.weather_date = row.weather_date?.slice(0, 10) ?? ''
  form.rainfall_mm = row.rainfall_mm
  form.wind_speed = row.wind_speed
  form.temperature = row.temperature
  showForm.value = true
}

async function save() {
  if (editing.value) {
    await apiFetch(`/v1/weather/${editing.value.id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form),
      parseJson: true,
    })
  } else {
    await apiFetch('/v1/weather', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form),
      parseJson: true,
    })
  }
  showForm.value = false
  weather.value = ((await apiFetch('/v1/weather', { parseJson: true })) as any).data
}

async function remove(row: any) {
  if (!confirm('Delete this weather record?')) return
  await apiFetch(`/v1/weather/${row.id}`, { method: 'DELETE' })
  weather.value = weather.value.filter((w: any) => w.id !== row.id)
}

const weatherLabel = (w: any) => {
  const parts: string[] = []
  if (w.rainfall_mm !== null && w.rainfall_mm !== undefined) parts.push(`${w.rainfall_mm}mm rain`)
  if (w.temperature !== null && w.temperature !== undefined) parts.push(`${w.temperature}°C`)
  if (w.wind_speed !== null && w.wind_speed !== undefined) parts.push(`${w.wind_speed}km/h wind`)
  return parts.length ? parts.join(', ') : 'No weather data'
}

type Weather = {
  id: number
  weather_date: string
  rainfall_mm: number | null
  wind_speed: number | null
  temperature: number | null
}

const columns: TableColumn<Weather>[] = [
  { accessorKey: 'id', header: '#' },
  {
    accessorKey: 'weather_date',
    header: 'Date',
    cell: ({ row }) => new Date(row.getValue('weather_date')).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }),
  },
  {
    accessorKey: 'rainfall_mm',
    header: 'Rainfall',
    meta: { class: { th: 'text-right', td: 'text-right font-mono' } },
  },
  {
    accessorKey: 'wind_speed',
    header: 'Wind Speed',
    meta: { class: { th: 'text-right', td: 'text-right font-mono' } },
  },
  {
    accessorKey: 'temperature',
    header: 'Temperature',
    meta: { class: { th: 'text-right', td: 'text-right font-mono' } },
  },
  { accessorKey: 'action', header: 'Action' },
]
</script>

<template>
  <div class="p-6 space-y-5">
    <div class="flex justify-between">
      <h1 class="text-2xl font-bold">Weather Data</h1>
      <UButton v-if="can('manage settings')" icon="i-lucide-plus" @click="openCreate"> Add Record </UButton>
    </div>

    <UTable :data="weather" :columns="columns" :loading="loading">
      <template #action-cell="{ row }">
        <UButton v-if="can('manage settings')" size="xs" @click="openEdit(row.original)"> Edit </UButton>
        <UButton v-if="can('manage settings')" size="xs" color="error" variant="ghost" @click="remove(row.original)"> Delete </UButton>
      </template>
    </UTable>

    <UModal v-model:open="showForm">
      <UCard>
        <template #header> {{ editing ? 'Edit Weather Record' : 'New Weather Record' }} </template>
        <div class="space-y-4">
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
        </div>
        <template #footer>
          <div class="flex justify-end gap-2">
            <UButton variant="ghost" @click="showForm = false">Cancel</UButton>
            <UButton @click="save">Save</UButton>
          </div>
        </template>
      </UCard>
    </UModal>
  </div>
</template>
