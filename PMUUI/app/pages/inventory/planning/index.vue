<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { apiFetch, loading } from '~/composables/useApiFetch'
import { onMounted, ref, computed, h, reactive } from 'vue'
import { getPaginationRowModel } from '@tanstack/vue-table'
import { useTablePagination } from '~/composables/useTablePagination'
import { useToast } from '#imports'
import { usePermissions } from '~/composables/usePermissions'
import { useRouteRefresh } from '~/composables/useRouteRefresh'
import Loading from '~/components/Loading.vue'

definePageMeta({
  layout: 'dashboard',
})

const { can } = usePermissions()
const toast = useToast()
const { registerRefresh } = useRouteRefresh()

const planning = ref<any>(null)

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

const showEditModal = ref(false)
const editingItem = ref<any>(null)
const saving = ref(false)

const editForm = reactive({
  id: 0,
  item_name: '',
  recommended_min: 0,
})

async function openEdit(row: any) {
  editingItem.value = row
  editForm.id = row.item_id ?? row.id
  editForm.item_name = row.item_name
  editForm.recommended_min = row.recommended_min ?? row.minimum_stock ?? 0
  showEditModal.value = true
}

async function saveEdit() {
  console.log("Row ID:", editingItem.value.id);
  if (!editingItem.value) return
  saving.value = true
  try {
    await apiFetch(`/v1/inventory/planning/items/${editForm.id}/minimum-stock`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: editForm.id , minimum_stock: editForm.recommended_min }),
      parseJson: true,
    })
    toast.add({ title: 'Minimum stock updated', color: 'success' })
    showEditModal.value = false
    planning.value = null
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
  } catch (e: any) {
    toast.add({
      title: 'Failed to update minimum stock',
      description: e.message ?? 'Please try again.',
      color: 'error',
    })
  } finally {
    saving.value = false
  }
}

async function loadPlanningData() {
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
}

onMounted(async () => {
  await loadPlanningData()
  registerRefresh(loadPlanningData)
})

const offPeakRevenue = computed(() => planning.value?.off_peak_season?.total_revenue ?? 0)
const totalItems = computed(() => planning.value?.inventory_summary?.total_items ?? 0)
const lowStockCount = computed(() => planning.value?.inventory_summary?.low_stock_items ?? 0)
const totalQuantity = computed(() => planning.value?.inventory_summary?.total_quantity ?? 0)

const projectedRevenue = computed(() => {
  const forecasts = planning.value?.forecasts ?? []
  if (!forecasts.length) return 0
  const now = new Date()
  const currentMonthStart = new Date(now.getFullYear(), now.getMonth(), 1)
  const currentMonthEnd = new Date(now.getFullYear(), now.getMonth() + 1, 0)
  return forecasts
    .filter((f: any) => {
      const date = new Date(f.forecast_date)
      return date >= currentMonthStart && date <= currentMonthEnd
    })
    .reduce((sum: number, f: any) => sum + Number(f.predicted_revenue ?? 0), 0)
})

const neededBudget = computed(() => {
  const items = lowStockItemsArray.value
  if (!items.length) return 0
  return items.reduce((sum: number, item: any) => {
    const qty = Number(item.minimum_stock ?? item.recommended_min ?? 0) - Number(item.current_quantity ?? item.quantity ?? 0)
    const price = Number(item.price ?? item.unit_price ?? 0)
    return sum + Math.max(0, qty) * price
  }, 0)
})

const remainingBudget = computed(() => {
  return Number(projectedRevenue.value) - Number(neededBudget.value)
})

const canAffordItem = (item: any): boolean => {
  const neededQty = Math.max(0, Number(item.minimum_stock ?? item.recommended_min ?? 0) - Number(item.current_quantity ?? item.quantity ?? 0))
  const price = Number(item.price ?? item.unit_price ?? 0)
  const itemCost = neededQty * price
  return itemCost <= Number(remainingBudget.value)
}

const inventoryRecommendation = computed(() => {
  const budget = Number(remainingBudget.value)
  const lowStockItems = lowStockItemsArray.value

  if (budget < 0) {
    return {
      type: 'overstocked' as const,
      title: 'Budget Exceeded',
      message: 'Planned purchases exceed available budget. Reduce quantities to avoid overspending.',
      items: [] as Array<{ name: string; canAfford: boolean }>,
      bgClass: 'bg-red-50',
      borderClass: 'border-red-200',
      borderLeftClass: 'border-l-red-500',
      icon: 'i-lucide-alert-triangle',
      iconClass: 'text-red-500',
      titleClass: 'text-red-600',
      textClass: 'text-red-700'
    }
  }

  if (lowStockItems.length > 0) {
    const items = lowStockItems.map((item: any) => ({
      name: item.item_name,
      canAfford: canAffordItem(item)
    }))
    return {
      type: 'understocked' as const,
      title: 'Understocked Items',
      message: `${lowStockItems.length} item(s) below minimum. Budget: ${currency(budget)} available.`,
      items,
      bgClass: 'bg-amber-50',
      borderClass: 'border-amber-200',
      borderLeftClass: 'border-l-amber-500',
      icon: 'i-lucide-package-plus',
      iconClass: 'text-amber-500',
      titleClass: 'text-amber-600',
      textClass: 'text-amber-700'
    }
  }

  return {
    type: 'normal' as const,
    title: 'Stock Levels Healthy',
    message: `Budget: ${currency(budget)} available. No purchases needed.`,
    items: [] as Array<{ name: string; canAfford: boolean }>,
    bgClass: 'bg-emerald-50',
    borderClass: 'border-emerald-200',
    borderLeftClass: 'border-l-emerald-500',
    icon: 'i-lucide-check-circle-2',
    iconClass: 'text-emerald-500',
    titleClass: 'text-emerald-600',
    textClass: 'text-emerald-700'
  }
})

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
  low_stock: 'error',
  damaged: 'error',
  inactive: 'neutral',
}

// Helper functions for computed stock status
const computeStockStatus = (item: any): string => {
  if (item.status === 'damaged') return 'damaged'
  return item.quantity <= (item.minimum_stock ?? 0) ? 'low_stock' : 'available'
}

const computeDaysRemaining = (item: any): number | null => {
  const usage = item.average_daily_usage ?? 0
  if (usage > 0) return item.quantity / usage
  return null
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

const columns = computed(() => {
  const cols: TableColumn<any>[] = [
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
    // { accessorKey: 'minimum_stock', header: 'Min Stock', cell: ({ row }) => row.getValue('minimum_stock') ?? 0 },
    // { accessorKey: 'reorder_quantity', header: 'Restock Qty', cell: ({ row }) => row.getValue('reorder_quantity') ?? 0 },
    // { accessorKey: 'average_daily_usage', header: 'Avg Daily Usage', cell: ({ row }) => row.g1etValue('average_daily_usage') ?? 0 },
    // { accessorKey: 'estimated_monthly_usage', header: 'Est. Monthly Usage' },
    { accessorKey: 'recommended_min', header: 'Recommended Min' },
    // { accessorKey: 'reorder_point', header: 'Reorder Point' },
    {
      accessorKey: 'stock_status', header: 'Stock Status', cell: ({ row }) => {
        const item = row.original
        const s = computeStockStatus(item)
        return h('UBadge', { variant: 'subtle', color: statusColor[s] || 'neutral' }, () => s.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()))
      }
    },
    // {
    //   accessorKey: 'days_remaining', header: 'Days Remaining', cell: ({ row }) => {
    //     const item = row.original
    //     const days = computeDaysRemaining(item)
    //     return days !== null ? days.toFixed(1) : 'N/A'
    //   }
    // },
    {
      accessorKey: 'needs_reorder', header: 'Restock?', cell: ({ row }) => {
        return row.getValue('needs_reorder') ? 'Yes' : 'No'
      }
    },
  ]

  if (can('edit inventory')) {
    cols.push({ accessorKey: 'action', header: 'Action' })
  }

  return cols
})
</script>

<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold">Inventory Planning</h1>
        <p class="text-slate-500">Inventory and resource planning based on current stock and revenue forecasts.</p>
      </div>
    
    </div>

    <div v-if="loading" class="flex items-center justify-center py-20">
      <Loading size="48" color="#3b82f6" />
    </div>

    <template v-else-if="planning">
      <!-- Inventory Summary -->
      <UCard>
        <template #header>Inventory Summary</template>
        <div class="grid gap-4 sm:grid-cols-3">
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

      <!-- Inventory Recommendation -->
      <UCard :class="[inventoryRecommendation.bgClass, inventoryRecommendation.borderClass, inventoryRecommendation.borderLeftClass, 'border-l-4']">
        <div class="flex items-start gap-3 p-4">
          <div class="flex-shrink-0 w-10 h-10 rounded-full bg-white/60 flex items-center justify-center">
            <i :class="[inventoryRecommendation.icon, inventoryRecommendation.iconClass, 'text-2xl']"></i>
          </div>
          <div class="flex-1 min-w-0">
            <h3 class="font-semibold" :class="inventoryRecommendation.titleClass">{{ inventoryRecommendation.title }}</h3>
            <p class="text-sm mt-1" :class="inventoryRecommendation.textClass">{{ inventoryRecommendation.message }}</p>
            <div v-if="inventoryRecommendation.type === 'understocked' && inventoryRecommendation.items.length" class="mt-3 flex flex-wrap gap-1.5">
              <span v-for="item in inventoryRecommendation.items" :key="item.name"
                class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full"
                :class="item.canAfford ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'">
                {{ item.name }}
                <span class="w-1.5 h-1.5 rounded-full" :class="item.canAfford ? 'bg-emerald-500' : 'bg-red-500'"></span>
              </span>
            </div>
          </div>
        </div>
      </UCard>

      <!-- Budget Overview -->
      <UCard>
        <template #header>Budget Overview</template>
        <div class="grid gap-4 sm:grid-cols-3">
          <div>
            <p class="text-sm text-slate-500">Projected Revenue</p>
            <p class="text-xl font-bold text-primary">{{ currency(projectedRevenue) }}</p>
          </div>
          <div>
            <p class="text-sm text-slate-500">Needed Budget</p>
            <p class="text-xl font-bold text-warning">{{ currency(neededBudget) }}</p>
          </div>
          <div>
            <p class="text-sm text-slate-500">Remaining Budget</p>
            <p class="text-xl font-bold" :class="remainingBudget >= 0 ? 'text-success' : 'text-error'">{{ currency(remainingBudget) }}</p>
          </div>
        </div>
      </UCard>

      <!-- Recommended Stock Levels -->
      <UCard>
        <template #header>Recommended Stock Levels</template>
        <UTable :data="recommendedStockArray" :columns="columns"
          :pagination-options="{ getPaginationRowModel: getPaginationRowModel() }"
          v-model:pagination="overviewTablePagination">
        <template #action-cell="{ row }">
          <UButton
            v-if="can('edit inventory')"
            size="xs"
            color="info"
            @click="openEdit(row.original)"
            icon="i-lucide-edit"
          ></UButton>
        </template>
      </UTable>

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
            <UBadge :color="computeStockStatus(item) === 'damaged' ? 'error' : 'warning'" variant="subtle">
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

    <!-- Edit Minimum Stock Modal -->
    <UModal v-model:open="showEditModal">
      <template #header>
        Edit Minimum Stock
      </template>
      <template #body>
        <div class="space-y-4">
          <p class="text-sm text-slate-500">Item: <span class="font-medium">{{ editForm.item_name }}</span></p>
          <UFormField label="Minimum Stock (Recommended Min)" class="mb-3">
            <UInput type="number" v-model="editForm.recommended_min" class="w-full" min="0" />
          </UFormField>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-2">
          <UButton variant="ghost" @click="showEditModal = false">Close</UButton>
          <UButton @click="saveEdit" :loading="saving">
            Save
          </UButton>
        </div>
      </template>
    </UModal>
  </div>
</template>
