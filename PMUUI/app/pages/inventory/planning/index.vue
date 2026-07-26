<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted, ref, computed } from 'vue'

definePageMeta({
  layout: 'dashboard',
})

const planning = ref<any>(null)
const loading = ref(true)

const currency = (v: number) =>
  new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(v)

const activeTab = ref<'overview' | 'planning'>('overview')

onMounted(async () => {
  loading.value = true
  planning.value = (await apiFetch('/v1/inventory/planning', { parseJson: true })) as any
  loading.value = false
})

const peakRevenue = computed(() => planning.value?.peak_season?.total_revenue ?? 0)
const offPeakRevenue = computed(() => planning.value?.off_peak_season?.total_revenue ?? 0)
const totalItems = computed(() => planning.value?.inventory_summary?.total_items ?? 0)
const lowStockCount = computed(() => planning.value?.inventory_summary?.low_stock_items ?? 0)
const totalQuantity = computed(() => planning.value?.inventory_summary?.total_quantity ?? 0)

const budgetGuidance = computed(() => planning.value?.budget_guidance ?? {})

const categoryTypeColors: Record<string, string> = {
  equipment: 'primary',
  materials: 'success',
  supplies: 'warning',
}

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

const planColumns: TableColumn<any>[] = [
  { accessorKey: 'item_name', header: 'Item Name' },
  { accessorKey: 'category_type', header: 'Type' },
  { accessorKey: 'current_quantity', header: 'Current Qty' },
  { accessorKey: 'recommended_min', header: 'Recommended Min' },
  { accessorKey: 'needs_reorder', header: 'Needs Reorder?' },
]
</script>

<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold">Inventory Planning</h1>
        <p class="text-slate-500">Budgeting and resource planning for peak and off-peak seasons.</p>
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
        <div class="grid gap-6 md:grid-cols-2">
          <UCard>
            <template #header>Peak Season (Jan – Jun)</template>
            <div class="space-y-2">
              <p class="text-2xl font-bold text-primary">{{ currency(peakRevenue) }}</p>
              <p class="text-sm text-slate-500">{{ planning.peak_season.forecast_count }} forecast period(s)</p>
            </div>
          </UCard>
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

        <!-- Budget Guidance -->
        <UCard v-if="budgetGuidance.peak_budget_allocation">
          <template #header>Budget Allocation Guidance</template>
          <div class="grid gap-6 md:grid-cols-2">
            <div>
              <h3 class="font-semibold mb-3">Peak Season Budget</h3>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span>Equipment</span><span class="font-mono">{{ currency(budgetGuidance.peak_budget_allocation.equipment) }}</span></div>
                <div class="flex justify-between"><span>Materials</span><span class="font-mono">{{ currency(budgetGuidance.peak_budget_allocation.materials) }}</span></div>
                <div class="flex justify-between"><span>Supplies</span><span class="font-mono">{{ currency(budgetGuidance.peak_budget_allocation.supplies) }}</span></div>
              </div>
            </div>
            <div>
              <h3 class="font-semibold mb-3">Off-Peak Season Budget</h3>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span>Equipment</span><span class="font-mono">{{ currency(budgetGuidance.off_peak_budget_allocation.equipment) }}</span></div>
                <div class="flex justify-between"><span>Materials</span><span class="font-mono">{{ currency(budgetGuidance.off_peak_budget_allocation.materials) }}</span></div>
                <div class="flex justify-between"><span>Supplies</span><span class="font-mono">{{ currency(budgetGuidance.off_peak_budget_allocation.supplies) }}</span></div>
              </div>
            </div>
          </div>
        </UCard>

        <!-- Recommended Stock Levels -->
        <UCard>
          <template #header>Recommended Stock Levels</template>
          <UTable :data="Array.isArray(planning.recommended_stock) ? planning.recommended_stock : []" :columns="columns" />
        </UCard>

        <!-- Inventory by Category Type -->
        <UCard v-if="planning.inventory_summary?.by_category_type">
          <template #header>Inventory by Category Type</template>
          <div class="grid gap-4 sm:grid-cols-3">
            <UCard v-for="(data, type) in planning.inventory_summary.by_category_type" :key="type" :style="{ borderLeftColor: type === 'equipment' ? 'var(--color-primary)' : type === 'materials' ? 'var(--color-success)' : 'var(--color-warning)' }" style="border-left: 4px solid;">
              <p class="text-sm font-semibold capitalize">{{ type }}</p>
              <p class="text-2xl font-bold">{{ data.total_items }} items</p>
              <p class="text-sm text-slate-500">{{ data.total_quantity }} total qty</p>
              <p class="text-sm text-warning">{{ data.low_stock_count }} low stock</p>
            </UCard>
          </div>
        </UCard>
      </div>

      <!-- Planning View Tab -->
      <div v-show="activeTab === 'planning'" class="space-y-6">
        <UCard>
          <template #header>Peak Season Items (Jan – Jun)</template>
          <p class="text-sm text-slate-500 mb-4">Items requiring attention during peak season for resource planning and LGU budget preparation.</p>
          <UTable :data="planning.items?.filter((i: any) => i.needs_reorder) ?? []" :columns="planColumns" />
          <p v-if="!planning.items?.filter((i: any) => i.needs_reorder)?.length" class="text-sm text-slate-400 py-4">No items need reordering during peak season.</p>
        </UCard>

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
            <UCard v-for="f in planning.forecasts?.slice(0, 6) ?? []" :key="f.id" size="sm">
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