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

const normalizeCategoryType = (type: string): string => {
  return type.toLowerCase().replace(/\s+/g, '_')
}

const getCategoryColor = (type: string): string => {
  const normalized = normalizeCategoryType(type)
  const categoryTypeColors: Record<string, string> = {
    equipment: 'primary',
    materials: 'success',
    supplies: 'warning',
  }
  return categoryTypeColors[normalized] || 'neutral'
}

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

const categoryTypes = computed(() => {
  const byCategory = planning.value?.inventory_summary?.by_category_type ?? {}
  return Object.entries(byCategory).map(([type, data]: [string, any]) => ({
    type,
    totalItems: data.total_items ?? 0,
    totalQuantity: data.total_quantity ?? 0,
    lowStockCount: data.low_stock_count ?? 0,
  }))
})

const statusColor: Record<string, string> = {
  available: 'success',
  low_stock: 'warning',
  damaged: 'error',
}

const lowStockItemsArray = computed(() => {
  const items = planning.value?.low_stock_items
  if (!items) return []
  if (Array.isArray(items)) return items
  if (typeof items.toArray === 'function') return items.toArray()
  return Object.values(items)
})

const recommendedStockArray = computed(() => {
  const stock = planning.value?.recommended_stock
  if (!stock) return []
  if (Array.isArray(stock)) return stock
  if (typeof stock.toArray === 'function') return stock.toArray()
  return Object.values(stock)
})

const columns: TableColumn<any>[] = [
  { accessorKey: 'item_name', header: 'Item Name' },
  {
    accessorKey: 'category_type', header: 'Category', cell: ({ row }) => {
      const type = row.getValue('category_type')
      return h('UBadge', { variant: 'subtle', color: getCategoryColor(type) }, () => type)
    }
  },
  {
    accessorKey: 'current_quantity', header: 'Current Qty', cell: ({ row }) => {
      const qty = row.getValue('current_quantity')
      return h('span', { class: qty <= 5 ? 'text-warning font-semibold' : '' }, () => qty)
    }
  },
  { accessorKey: 'estimated_monthly_usage', header: 'Est. Monthly Usage' },
  { accessorKey: 'recommended_min', header: 'Recommended Min' },
  { accessorKey: 'reorder_point', header: 'Reorder Point' },
  {
    accessorKey: 'status', header: 'Status', cell: ({ row }) => {
      const s = row.getValue('status')
      return h('UBadge', { variant: 'subtle', color: statusColor[s] || 'neutral' }, () => s)
    }
  },
  {
    accessorKey: 'needs_reorder', header: 'Reorder?', cell: ({ row }) => {
      return row.getValue('needs_reorder') ? 'Yes' : 'No'
    }
  },
]
</script>

<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold">Inventory Planning</h1>
        <p class="text-slate-500">Inventory and resource planning based on current stock and revenue forecasts.</p>
      </div>
      <!-- <UButton icon="i-lucide-refresh-cw" :loading="loading" @click="() => window.location.reload()">
        Refresh
      </UButton> -->
    </div>

    <div v-if="loading" class="flex items-center justify-center py-20">
      <span class="text-slate-400">Loading planning data...</span>
    </div>

    <template v-else-if="planning">
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
        <UTable :data="recommendedStockArray" :columns="columns"
          :pagination-options="{ getPaginationRowModel: getPaginationRowModel() }"
          v-model:pagination="overviewTablePagination" />

        <div class="flex items-center justify-between mt-4">
          <div class="flex items-center gap-2">
            <span class="text-sm text-slate-500">Rows per page:</span>
            <USelect v-model="overviewPageSize" :items="[5, 10, 20, 30, 50]" class="w-20" />
          </div>
          <div class="flex items-center gap-2">
            <span class="text-sm text-slate-500">Go to page:</span>
            <UInput v-model="overviewGoToPageInput" type="number" :min="1" :max="overviewTotalPages" class="w-16"
              @keyup.enter="overviewHandleGoToPage" />
            <UButton size="sm" @click="overviewHandleGoToPage">Go</UButton>
          </div>
          <UPagination :total="recommendedStockArray.length"
            v-model:page="overviewPage" :items-per-page="overviewPageSize" />
        </div>
      </UCard>

      <!-- Inventory by Category Type -->
      <UCard v-if="categoryTypes.length">
        <template #header>Inventory by Category Type</template>
        <div class="grid gap-4 sm:grid-cols-3">
          <div v-for="category in categoryTypes" :key="category.type" class="rounded-lg border p-4"
            :style="{ borderLeftColor: getCategoryColor(category.type) ? `var(--color-${getCategoryColor(category.type)})` : 'var(--color-neutral)', borderLeftStyle: 'solid', borderLeftWidth: '4px' }">
            <p class="text-sm font-semibold capitalize">{{ category.type.replace(/_/g, ' ') }}</p>
            <p class="text-2xl font-bold">{{ category.totalItems }} items</p>
            <p class="text-sm text-slate-500">{{ category.totalQuantity }} total qty</p>
            <p class="text-sm text-warning">{{ category.lowStockCount }} low stock</p>
          </div>
        </div>
      </UCard>

      <!-- Low Stock Alerts -->
      <UCard>
        <template #header>Low Stock Alerts</template>
        <div v-if="lowStockItemsArray.length" class="space-y-3">
          <div v-for="item in lowStockItemsArray" :key="item.id || item.item_name"
            class="flex items-center justify-between py-2 border-b last:border-0">
            <div>
              <p class="text-sm font-medium">{{ item.item_name }}</p>
              <p class="text-xs text-slate-500 capitalize">{{ item.category_type || item.category }} • {{ item.category }}</p>
            </div>
            <UBadge :color="item.status === 'damaged' ? 'error' : 'warning'" variant="subtle">
              {{ item.current_quantity ?? item.quantity }} left
            </UBadge>
          </div>
        </div>
        <p v-else class="text-sm text-slate-400">All inventory levels are healthy.</p>
      </UCard>

      <!-- Forecast Overview -->
      <!-- <UCard>
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
      </UCard> -->
    </template>
  </div>
</template>
