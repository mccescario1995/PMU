<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted } from 'vue'

definePageMeta({
  layout: "dashboard",
});

const UBadge = resolveComponent('UBadge')

const currency = (value: number) =>
  new Intl.NumberFormat("en-US", { style: "currency", currency: "PHP" }).format(value)

const forecasts = ref<any[]>([])

onMounted(async () => {
  forecasts.value = (await apiFetch('/v1/forecasts', { parseJson: true })) as any[]
})

type Forecast = {
  id: number
  forecast_date: string
  predicted_revenue: number
  season: string
  model_version: string
}

const totalRevenue = computed(() => forecasts.value.reduce((sum, f) => sum + (f.predicted_revenue ?? 0), 0))
const periods = computed(() => forecasts.value.length)
const latestModel = computed(() => forecasts.value[0]?.model_version ?? "-")

const columns: TableColumn<Forecast>[] = [
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
    accessorKey: 'model_version',
    header: 'Model Version',
    cell: ({ row }) => row.getValue("model_version"),
  },
]
</script>

<template>
  <div class="p-6 space-y-6">
    <div>
      <h1 class="text-2xl font-bold">Forecasting</h1>
      <p class="text-slate-500">Projected revenue and volume based on historical operations.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
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
