<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted } from 'vue'

definePageMeta({
  layout: "dashboard",
});

const currency = (value: number) =>
  new Intl.NumberFormat("en-US", { style: "currency", currency: "PHP" }).format(value)

const year = new Date().getFullYear()

const years = ref<any[]>([])
const totalRevenue = ref(0)

onMounted(async () => {
  const report = (await apiFetch('/v1/reports/annual?year=' + year, { parseJson: true })) as any
  totalRevenue.value = Number(report.total_revenue ?? 0)
  years.value = (report.rows ?? []).map((r: any) => ({
    revenue_date: r.revenue_date,
    revenue: r.total_revenue,
    transactions: r.transaction_count,
  }))
})

function exportCsv() {
  window.open('/v1/reports/annual/excel?year=' + year, '_blank')
}

function exportPdf() {
  window.open('/v1/reports/annual/pdf?year=' + year, '_blank')
}

function exportXlsx() {
  window.open('/v1/reports/annual/xlsx?year=' + year, '_blank')
}

type Year = {
  revenue_date: string
  revenue: number
  transactions: number
}

const columns: TableColumn<Year>[] = [
  {
    accessorKey: 'revenue_date',
    header: 'Date',
    cell: ({ row }) => new Date(row.getValue('revenue_date')).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }),
  },
  {
    accessorKey: 'revenue',
    header: 'Revenue',
    meta: { class: { th: "text-right", td: "text-right font-mono" } },
    cell: ({ row }) => currency(Number(row.getValue("revenue"))),
  },
  {
    accessorKey: 'transactions',
    header: 'Transactions',
    meta: { class: { th: "text-right", td: "text-right font-mono" } },
  },
]
</script>

<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold">Yearly Report</h1>
        <p class="text-slate-500">Performance and trends for {{ year }}.</p>
      </div>
      <UButton icon="i-lucide-download" @click="exportCsv"> Export CSV </UButton>
      <UButton icon="i-lucide-file-spreadsheet" variant="outline" @click="exportXlsx"> Export Excel </UButton>
      <UButton icon="i-lucide-file-text" variant="outline" @click="exportPdf"> Export PDF </UButton>
    </div>

    <UCard>
      <template #header> Cumulative Revenue </template>
      <p class="text-2xl font-bold text-success">{{ currency(totalRevenue) }}</p>
    </UCard>

    <UTable :data="years" :columns="columns" />
  </div>
</template>
