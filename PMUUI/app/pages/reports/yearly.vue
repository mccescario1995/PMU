<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted, computed, watch, ref } from 'vue'
import { getPaginationRowModel } from '@tanstack/vue-table'
import { useTablePagination } from '~/composables/useTablePagination'

definePageMeta({
  layout: "dashboard",
});

const currency = (value: number) =>
  new Intl.NumberFormat("en-US", { style: "currency", currency: "PHP" }).format(value)

const year = new Date().getFullYear()

const years = ref<any[]>([])
const { page, pageSize, pageSizeNumber, goToPageInput, tablePagination, totalPages, handleGoToPage } = useTablePagination(() => years.value.length)
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

    <UTable :data="years" :columns="columns" :pagination-options="{ getPaginationRowModel: getPaginationRowModel() }" v-model:pagination="tablePagination" />

    <div class="flex items-center justify-between mt-4">
      <div class="flex items-center gap-2">
        <span class="text-sm text-slate-500">Rows per page:</span>
        <USelect v-model="pageSize" :items="[5, 10, 20, 30, 50]" class="w-20" />
      </div>
      <div class="flex items-center gap-2">
        <span class="text-sm text-slate-500">Go to page:</span>
        <UInput v-model="goToPageInput" type="number" :min="1" :max="totalPages" class="w-16" @keyup.enter="handleGoToPage" />
        <UButton size="sm" @click="handleGoToPage">Go</UButton>
      </div>
      <UPagination :total="years.length" v-model:page="page" :items-per-page="pageSizeNumber" />
    </div>
  </div>
</template>
