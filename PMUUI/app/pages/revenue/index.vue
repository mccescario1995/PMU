<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted, ref, watch } from 'vue'
import { getPaginationRowModel } from '@tanstack/vue-table'
import { useTablePagination } from '~/composables/useTablePagination'

definePageMeta({
  layout: "dashboard",
});

const UBadge = resolveComponent('UBadge')

const currency = (value: number) =>
  new Intl.NumberFormat("en-US", { style: "currency", currency: "PHP" }).format(value)

const revenue = ref<any[]>([])
const { page, pageSize, pageSizeNumber, goToPageInput, tablePagination, totalPages, handleGoToPage } = useTablePagination(() => revenue.value.length)
const selectedYear = ref<number | null>(null)
const years = [
  { label: 'All', value: null },
  { label: '2025', value: 2025 },
  { label: '2024', value: 2024 },
  { label: '2023', value: 2023 },
  { label: '2022', value: 2022 },
  { label: '2021', value: 2021 },
  { label: '2020', value: 2020 },
]

const fetchRevenue = async (year: number | null) => {
  const url = year
    ? `/v1/dashboard/revenue-breakdown?year=${year}`
    : '/v1/dashboard/revenue-breakdown'
  revenue.value = (await apiFetch(url, { parseJson: true })) as any[]
}

onMounted(() => {
  fetchRevenue(selectedYear.value)
})

watch(selectedYear, (year) => {
  fetchRevenue(year)
})

type Revenue = {
  source: string
  amount: number
  count: number
}

const total = computed(() => revenue.value.reduce((sum, r) => sum + Number(r.amount ?? 0), 0))
const totalCount = computed(() => revenue.value.reduce((sum, r) => sum + Number(r.count ?? 0), 0))
const sources = computed(() => revenue.value.length)

const columns: TableColumn<Revenue>[] = [
  {
    accessorKey: 'source',
    header: 'Revenue Source',
  },
  {
    accessorKey: 'amount',
    header: 'Amount',
    meta: { class: { th: "text-right", td: "text-right font-mono" } },
    cell: ({ row }) => currency(Number(row.getValue("amount"))),
  },
  {
    accessorKey: 'count',
    header: 'Transactions',
    meta: { class: { th: "text-right", td: "text-right font-mono" } },
    cell: ({ row }) => row.getValue("count"),
  },
]
</script>

<template>
  <div class="p-6 space-y-6">
    <div class="flex items-end justify-between">
      <div>
        <h1 class="text-2xl font-bold">Revenue</h1>
        <p class="text-slate-500">Breakdown of income by port operation fee.</p>
      </div>
      <USelect
        v-model="selectedYear"
        :items="years"
        placeholder="Select year"
        class="w-40"
      />
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
      <UCard>
        <template #header> Total Revenue </template>
        <p class="text-2xl font-bold text-success">{{ currency(total) }}</p>
      </UCard>
      <UCard>
        <template #header> Total Transactions </template>
        <p class="text-2xl font-bold">{{ totalCount }}</p>
      </UCard>
      <UCard>
        <template #header> Revenue Sources </template>
        <p class="text-2xl font-bold text-success">{{ sources }}</p>
      </UCard>
    </div>

    <UTable :data="revenue" :columns="columns" :pagination-options="{ getPaginationRowModel: getPaginationRowModel() }" v-model:pagination="tablePagination" />

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
      <UPagination :total="revenue.length" v-model:page="page" :items-per-page="pageSizeNumber" />
    </div>
  </div>
</template>
