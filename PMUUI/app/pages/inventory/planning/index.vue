<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted, ref, computed, h } from 'vue'
import { getPaginationRowModel } from '@tanstack/vue-table'
import { useTablePagination } from '~/composables/useTablePagination'

definePageMeta({
  layout: 'dashboard',
})

const planning = ref<any>(null)
const { page: overviewPage, pageSize: overviewPageSize, goToPageInput: overviewGoToPageInput, tablePagination: overviewTablePagination, totalPages: overviewTotalPages, handleGoToPage: overviewHandleGoToPage } = useTablePagination(() => Array.isArray(planning.value?.recommended_stock) ? planning.value.recommended_stock.length : 0)
const loading = ref(true)

const currency = (v: number) =>
  new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(v)

const activeTab = ref<'overview' | 'planning'>('overview')

onMounted(async () => {
  loading.value = true
  const [overview, view] = await Promise.all([
    apiFetch('/v1/inventory/planning', { parseJson: true }),
    apiFetch('/v1/inventory/planning/view', { parseJson: true }),
  ])

  planning.value = {
    ...overview,
    low_stock_items: view.low_stock_items ?? [],
    forecasts: view.forecasts ?? [],
  } as any
  loading.value = false
})

const offPeakRevenue = computed(() => planning.value?.off_peak_season?.total_revenue ?? 0)
const totalItems = computed(() => planning.value?.inventory_summary?.total_items ?? 0)
const lowStockCount = computed(() => planning.value?.inventory_summary?.low_stock_items ?? 0)
const totalQuantity = computed(() => planning.value?.inventory_summary?.total_quantity ?? 0)
const forecastOverview = computed(() =>
  (planning.value?.forecasts ?? [])
    .filter((forecast: any) => {
      const month = Number(String(forecast.forecast_date).slice(5, 7))
      return month >= 7 && month <= 12
    })
    .slice(0, 6)
)

const categoryTypeColors: Record<string, string> = {
  equipment: 'primary',
  materials: 'success',
  supplies: 'warning',
}

const categoryTypes = computed(() =>
  Object.entries(planning.value?.inventory_summary?.by_category_type ?? {}).map(([type, data]: [string, any]) => ({
    type,
    totalItems: data.total_items ?? 0,
    totalQuantity: data.total_quantity ?? 0,
    lowStockCount: data.low_stock_count ?? 0,
  }))
)

const statusColor: Record<string, string> = {
  available: 'success',
  low_stock: 'warning',
  damaged: 'error',
}

const columns: TableColumn<any>[] = [
  { accessorKey: 'item_name', header: 'Item Name' },
  { accessorKey: 'category_type', header: 'Category Type', cell: ({ row }) => {
    const type = row.getValue('category_type')
    return h('UBadge', { variant: 'subtle', color: categoryTypeColors[type] || 'neutral' }, () => type)
  }},
  { accessorKey: 'quantity', header: 'Current Qty' },
  { accessorKey: 'recommended_min', header: 'Recommended Min' },
  { accessorKey: 'status', header: 'Status', cell: ({ row }) => {
    const s = row.getValue('status')
    return h('UBadge', { variant: 'subtle', color: statusColor[s] || 'neutral' }, () => s)
  }},
  { accessorKey: 'needs_reorder', header: 'Reorder?', cell: ({ row }) => {
    return row.getValue('needs_reorder') ? 'Yes' : 'No'
  }},
]
</script>

<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold">Inventory Planning</h1>
        <p class="text-slate-500">Inventory and resource planning based on current stock and revenue forecasts.</p>
      </div>
      <UButton icon="i-lucide-refresh-cw" :loading="loading" @click="() => window.location.reload()">
        Refresh
      </UButton>
    </div>

    <UTabs v-model="activeTab" :items="[
      { label: 'Overview', value: 'overview' },
      { label: 'Planning View', value: 'planning' },
    ]" />

    <div v-if="loading" class="flex items-center justify-center py-20">
      <span class="text-slate-400">Loading planning data...</span>
    </div>

    <template v-else-if="planning">
      <!-- Overview Tab -->
      <div v-show="activeTab === 'overview'" class="space-y-6">
        <!-- Season Summary Cards -->
        <div class="grid gap-6 md:grid-cols-1">
          <UCard>
            <template #header>Off-Peak Season (Jul – Dec)</template>
            <div class="space-y-2">
              <p class="text-2xl font-bold text-success">{{ currency(offPeakRevenue) }}</p>
              <p class="text-sm text-slate-500">{{ planning.off_peak_season.forecast_count }} forecast period(s)</p>
            </div>
          </UCard>
        </div>

        <!-- Inventory Summary -->
        <UCard>
          <template #header>Inventory Summary</template>
          <div class="grid gap-4 sm:grid-cols-4">
            <div>
              <p class="text-sm text-slate-500">Total Items</p>
              <p class="text-xl font-bold">{{ totalItems }}</p>
            </div>
            <div>
              <p class="text-sm text-slate-500">Total Quantity</p>
              <p class="text-xl font-bold">{{ totalQuantity }}</p>
            </div>
            <div>
              <p class="text-sm text-slate-500">Low Stock</p>
              <p class="text-xl font-bold text-warning">{{ lowStockCount }}</p>
            </div>
          </div>
        </UCard>

        <!-- Recommended Stock Levels -->
        <UCard>
          <template #header>Recommended Stock Levels</template>
          <UTable :data="Array.isArray(planning.recommended_stock) ? planning.recommended_stock : []" :columns="columns" :pagination-options="{ getPaginationRowModel: getPaginationRowModel() }" v-model:pagination="overviewTablePagination" />

          <div class="flex items-center justify-between mt-4">
            <div class="flex items-center gap-2">
              <span class="text-sm text-slate-500">Rows per page:</span>
              <USelect v-model="overviewPageSize" :items="[5, 10, 20, 30, 50]" class="w-20" />
            </div>
            <div class="flex items-center gap-2">
              <span class="text-sm text-slate-500">Go to page:</span>
              <UInput v-model="overviewGoToPageInput" type="number" :min="1" :max="overviewTotalPages" class="w-16" @keyup.enter="overviewHandleGoToPage" />
              <UButton size="sm" @click="overviewHandleGoToPage">Go</UButton>
            </div>
            <UPagination :total="Array.isArray(planning.recommended_stock) ? planning.recommended_stock.length : 0" v-model:page="overviewPage" :items-per-page="overviewPageSize" />
          </div>
        </UCard>

        <UCard v-if="categoryTypes.length">
          <template #header>Inventory by Category Type</template>
          <div class="grid gap-4 sm:grid-cols-3">
            <div
              v-for="category in categoryTypes"
              :key="category.type"
              class="rounded-lg border p-4"
              :style="{ borderLeftColor: categoryTypeColors[category.type] ? `var(--color-${categoryTypeColors[category.type]})` : 'var(--color-neutral)', borderLeftStyle: 'solid', borderLeftWidth: '4px' }"
            >
              <p class="text-sm font-semibold capitalize">{{ category.type.replace(/_/g, ' ') }}</p>
              <p class="text-2xl font-bold">{{ category.totalItems }} items</p>
              <p class="text-sm text-slate-500">{{ category.totalQuantity }} total qty</p>
              <p class="text-sm text-warning">{{ category.lowStockCount }} low stock</p>
            </div>
          </div>
        </UCard>
      </div>

      <!-- Planning View Tab -->
      <div v-show="activeTab === 'planning'" class="space-y-6">
        <UCard>
          <template #header>Low Stock Alerts</template>
          <div v-if="planning.low_stock_items?.length" class="space-y-3">
            <div v-for="item in planning.low_stock_items" :key="item.id" class="flex items-center justify-between py-2 border-b last:border-0">
              <div>
                <p class="text-sm font-medium">{{ item.item_name }}</p>
                <p class="text-xs text-slate-500 capitalize">{{ item.category_type }} • {{ item.category }}</p>
              </div>
              <UBadge :color="item.status === 'damaged' ? 'error' : 'warning'" variant="subtle">
                {{ item.quantity }} left
              </UBadge>
            </div>
          </div>
          <p v-else class="text-sm text-slate-400">All inventory levels are healthy.</p>
        </UCard>

        <UCard>
          <template #header>Forecast Overview</template>
          <div class="grid gap-4 sm:grid-cols-2">
            <UCard v-for="f in forecastOverview" :key="f.id" size="sm">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium">{{ new Date(f.forecast_date).toLocaleDateString('en-US', { month: 'short', year: 'numeric' }) }}</p>
                  <p class="text-xs text-slate-500">{{ f.season ?? '—' }}</p>
                </div>
                <span class="text-sm font-semibold text-primary">{{ currency(f.predicted_revenue) }}</span>
              </div>
            </UCard>
          </div>
        </UCard>
      </div>
    </template>
  </div>
</template>