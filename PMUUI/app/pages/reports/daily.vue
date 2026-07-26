<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted } from 'vue'

definePageMeta({
  layout: "dashboard",
});

const currency = (value: number) =>
  new Intl.NumberFormat("en-US", { style: "currency", currency: "PHP" }).format(value)

const today = new Date().toISOString().slice(0, 10)

const transactions = ref<any[]>([])
const total = ref(0)
const count = ref(0)

function formatFeeTypes(items: any[]): string {
  if (!items || items.length === 0) return "-";
  if (items.length === 1) return items[0].fee_type?.fee_name ?? "-";
  return items.map((i: any) => i.fee_type?.fee_name).filter(Boolean).join(", ");
}

onMounted(async () => {
  const report = (await apiFetch('/v1/reports/daily?date=' + today, { parseJson: true })) as any
  total.value = Number(report.total ?? 0)
  count.value = Number(report.count ?? 0)
  transactions.value = (report.transactions ?? []).map((t: any) => ({
    id: t.id,
    type: formatFeeTypes(t.items ?? []),
    stakeholder: t.stakeholder?.name ?? "-",
    amount: t.total_amount,
    time: (t.transaction_date ?? "").toString().slice(11, 16) || "-",
  }))
})

function exportCsv() {
  window.open('/v1/reports/daily/excel?date=' + today, '_blank')
}

function exportPdf() {
  window.open('/v1/reports/daily/pdf?date=' + today, '_blank')
}

function exportXlsx() {
  window.open('/v1/reports/daily/xlsx?date=' + today, '_blank')
}

type Transaction = {
  id: number
  type: string
  stakeholder: string
  amount: number
  time: string
}

const columns: TableColumn<Transaction>[] = [
  {
    accessorKey: 'id',
    header: '#',
    cell: ({ row }) => `#${row.getValue('id')}`,
  },
  { accessorKey: 'type', header: 'Type(s)' },
  { accessorKey: 'stakeholder', header: 'Stakeholder' },
  { accessorKey: 'time', header: 'Time' },
  {
    accessorKey: 'amount',
    header: 'Amount',
    meta: { class: { th: "text-right", td: "text-right font-mono" } },
    cell: ({ row }) => currency(Number(row.getValue("amount"))),
  },
]
</script>

<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold">Daily Report</h1>
        <p class="text-slate-500">{{ new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}</p>
      </div>
      <UButton icon="i-lucide-download" @click="exportCsv"> Export CSV </UButton>
      <UButton icon="i-lucide-file-spreadsheet" variant="outline" @click="exportXlsx"> Export Excel </UButton>
      <UButton icon="i-lucide-file-text" variant="outline" @click="exportPdf"> Export PDF </UButton>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <UCard>
        <template #header> Transactions Today </template>
        <p class="text-2xl font-bold">{{ count }}</p>
      </UCard>
      <UCard>
        <template #header> Total Collection </template>
        <p class="text-2xl font-bold text-success">{{ currency(total) }}</p>
      </UCard>
    </div>

    <UTable :data="transactions" :columns="columns" />
  </div>
</template>
