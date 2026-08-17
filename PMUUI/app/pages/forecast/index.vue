<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted, watch } from 'vue'

definePageMeta({
  layout: "dashboard",
});

const UBadge = resolveComponent('UBadge')

const currency = (value: number) =>
  new Intl.NumberFormat("en-US", { style: "currency", currency: "PHP" }).format(value)

const forecasts = ref<any[]>([])
const loading = ref(true)
const showForm = ref(false)
const modelLoading = ref(false)
const modelError = ref("")

const form = reactive({
  forecast_date: new Date().toISOString().slice(0, 10),
  predicted_revenue: 0,
  season: "",
  model_version: "",
  weather: null as any,
})

async function runModel(model: string) {
  modelLoading.value = true
  modelError.value = ""
  try {
    await apiFetch(`/v1/forecasts/run-model`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ model, days: 30 }),
      parseJson: true,
      throwOnError: true,
    })
    await load()
  } catch (e: any) {
    modelError.value = e?.message || "Failed to run model"
  } finally {
    modelLoading.value = false
  }
}

async function loadWeather(date: string) {
  try {
    const w = await apiFetch(`/v1/weather?date=${date}`, { parseJson: true }) as any[]
    form.weather = w && w.length ? w[0] : null
  } catch {
    form.weather = null
  }
}

watch(() => form.forecast_date, (date) => {
  if (date) loadWeather(date)
})

function reset() {
  form.forecast_date = new Date().toISOString().slice(0, 10)
  form.predicted_revenue = 0
  form.season = ""
  form.model_version = ""
  form.weather = null
  showForm.value = false
}

async function submit() {
  await apiFetch('/v1/forecasts/generate', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      forecast_date: form.forecast_date,
      predicted_revenue: Number(form.predicted_revenue),
      season: form.season || null,
      model_version: form.model_version || null,
    }),
    parseJson: true,
    throwOnError: true,
  })
  reset()
  await load()
}

async function load() {
  loading.value = true
  forecasts.value = (await apiFetch('/v1/forecasts', { parseJson: true })) as any[]
  loading.value = false
}

onMounted(() => {
  load()
  loadWeather(form.forecast_date)
})

const weatherLabel = (w: any) => {
  if (!w) return "No weather data"
  const parts: string[] = []
  if (w.rainfall_mm !== null && w.rainfall_mm !== undefined) parts.push(`${w.rainfall_mm}mm rain`)
  if (w.temperature !== null && w.temperature !== undefined) parts.push(`${w.temperature}°C`)
  if (w.wind_speed !== null && w.wind_speed !== undefined) parts.push(`${w.wind_speed}km/h wind`)
  return parts.length ? parts.join(', ') : "No weather data"
}

const totalRevenue = computed(() => forecasts.value.reduce((sum, f) => sum + Number(f.predicted_revenue ?? 0), 0))
const periods = computed(() => forecasts.value.length)
const latestModel = computed(() => forecasts.value[0]?.model_version ?? "-")

const columns: TableColumn<any>[] = [
  {
    accessorKey: 'forecast_date',
    header: 'Period',
    cell: ({ row }) => new Date(row.getValue('forecast_date')).toLocaleDateString('en-US', { year: 'numeric', month: 'short' }),
  },
  {
    accessorKey: 'predicted_revenue',
    header: 'Projected Revenue',
    meta: { class: { th: "text-right", td: "text-right font-mono" } },
    cell: ({ row }) => currency(Number(row.getValue("predicted_revenue"))),
  },
  {
    accessorKey: 'season',
    header: 'Season',
    cell: ({ row }) => row.getValue("season"),
  },
  {
    header: 'Weather',
    cell: ({ row }) => row.original.weather ? weatherLabel(row.original.weather) : "No data",
  },
]
</script>

<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold">Forecasting</h1>
        <p class="text-slate-500">Projected revenue and volume based on historical operations.</p>
      </div>
      <div class="flex gap-2">
        <UButton
          icon="i-lucide-chart-line"
          :loading="modelLoading"
          @click="runModel('linear_regression')"
        >
          Run Linear
        </UButton>
        <UButton
          icon="i-lucide-chart-line"
          :loading="modelLoading"
          @click="runModel('amira')"
        >
          Run AMIRA
        </UButton>
        <UButton
          icon="i-lucide-chart-line"
          :loading="modelLoading"
          @click="runModel('samira')"
        >
          Run SAMIRA
        </UButton>
        <UButton icon="i-lucide-plus" @click="showForm = true; reset()"> Add Forecast </UButton>
      </div>
    </div>

    <UAlert v-if="modelError" type="error" :title="modelError" class="mb-4" />

    <UCard v-if="showForm">
      <template #header> New Forecast </template>
      <UForm @submit="submit">
        <div class="grid gap-4 sm:grid-cols-2">
          <UFormField label="Forecast Date" required>
            <UInput type="date" v-model="form.forecast_date" />
          </UFormField>
          <UFormField label="Predicted Revenue" required>
            <UInput type="number" v-model.number="form.predicted_revenue" />
          </UFormField>
          <UFormField label="Season">
            <UInput v-model="form.season" placeholder="e.g. Rainy, Dry" />
          </UFormField>
          <UFormField label="Model Version">
            <UInput v-model="form.model_version" placeholder="e.g. v1.0" />
          </UFormField>
          <UFormField label="Weather on Date">
            <p class="text-sm text-gray-500 py-2">{{ weatherLabel(form.weather) }}</p>
          </UFormField>
        </div>
        <div class="flex gap-2 mt-4">
          <UButton type="submit"> Save </UButton>
          <UButton variant="ghost" @click="reset"> Cancel </UButton>
        </div>
      </UForm>
    </UCard>

    <div class="grid gap-6 sm:grid-cols-3">
      <UCard>
        <template #header> Projected Revenue </template>
        <p class="text-2xl font-bold text-primary">{{ currency(totalRevenue) }}</p>
      </UCard>
      <UCard>
        <template #header> Forecast Periods </template>
        <p class="text-2xl font-bold text-primary">{{ periods }}</p>
      </UCard>
      <UCard>
        <template #header> Latest Model </template>
        <p class="text-2xl font-bold text-primary">{{ latestModel }}</p>
      </UCard>
    </div>

    <UTable :data="forecasts" :columns="columns" />
  </div>
</template>