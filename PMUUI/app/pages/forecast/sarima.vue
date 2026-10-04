<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { useForecast } from '~/composables/useForecast'
import { computed, ref } from 'vue'
import { getPaginationRowModel } from '@tanstack/vue-table'
import { useTablePagination } from '~/composables/useTablePagination'

definePageMeta({
  layout: "dashboard",
})

const {
    forecasts,
    loading,
    showForm,
    modelLoading,
    modelError,
    showProgressModal,
    progressSteps,
    progressError,
    form,
    modalMode,
    saving,
    viewing,
    reset,
    submit,
    remove,
    openCreate,
    openView,
    openEdit,
    currency,
    columns,
    can,
    load,
} = useForecast('/v1/forecasts/model/sarima', '/v1/forecasts/train/sarima', 'sarima')

const model = 'sarima'
const modelLabel = 'SARIMA'

// Year and month selection controls, defaulting to the current month
const selectedYear = ref(new Date().getFullYear())
const selectedMonth = ref(new Date().getMonth()) // null = all months, 0-11 = specific month

const monthNames = ["All Months", "Jan", "Feb", "Mar", "Apr", "May", "Jun",
                    "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"]

// Forecasts for this model only
const filteredForecasts = computed(() =>
  forecasts.value.filter((f: any) => {
    const mv = (f.model_version || '').toLowerCase()
    const slug = model.replace(/_/g, '-')
    return mv.includes(slug) || mv.includes(model)
  })
)

// Available years from forecast data
const availableYears = computed(() =>
  [...new Set(filteredForecasts.value
    .map((f: any) => new Date(f.forecast_date))
    .filter((d) => !isNaN(d.getTime()))
    .map((d) => d.getFullYear())
  )].sort()
)

// Forecasts for the selected year (and optionally month)
const selectedForecasts = computed(() =>
  filteredForecasts.value.filter((f: any) => {
    const d = new Date(f.forecast_date)
    if (isNaN(d.getTime())) return false
    if (d.getFullYear() !== selectedYear.value) return false
    return selectedMonth.value === null || d.getMonth() === selectedMonth.value
  })
)

// Group by month when showing all months
const monthlyAggregates = computed(() => {
  if (selectedMonth.value !== null) return []

  const months = Array.from({ length: 12 }, (_, i) => ({
    month: i,
    monthName: monthNames[i + 1], // +1 because index 0 is "All Months"
    totalRevenue: 0,
    count: 0,
    latestModel: '-'
  }))

  for (const f of selectedForecasts.value) {
    const m = months[new Date(f.forecast_date).getMonth()]! // getMonth() is 0-11
    m.totalRevenue += Number(f.predicted_revenue ?? 0)
    m.count += 1
    m.latestModel = f.model_version ?? '-'
  }

  return months.filter((m) => m.count > 0) // Only include months with data
})

// What to show in the table
const displayData = computed(() =>
  selectedMonth.value === null ? monthlyAggregates.value : selectedForecasts.value
)

const isDecemberForecast = computed(() => {
    const now = new Date()
    let nextMonth = now.getMonth() + 1
    if (nextMonth > 11) nextMonth = 0
    return nextMonth === 11
})

// Both views cover the same rows, so the total is identical either way
const totalRevenue = computed(() =>
  selectedForecasts.value.reduce((sum, f) => sum + Number(f.predicted_revenue ?? 0), 0)
)

const periods = computed(() => displayData.value.length)
const latestModel = computed(() => selectedForecasts.value[0]?.model_version ?? '-')

// Table columns based on what's being displayed
const getTableColumns = computed(() =>
  selectedMonth.value === null
    ? [
        { header: 'Month', accessorKey: 'monthName' },
        { header: 'Total Revenue', accessorKey: 'totalRevenue' },
        { header: 'Days with Data', accessorKey: 'count' },
        { header: 'Latest Model', accessorKey: 'latestModel' }
      ]
    : columns
)

const { page, pageSize, pageSizeNumber, goToPageInput, tablePagination, totalPages, handleGoToPage } = useTablePagination(() => displayData.value.length)

const paginationOptions = { getPaginationRowModel: getPaginationRowModel() }

const UBadge = resolveComponent('UBadge')
</script>

<template>
  <div class="p-6 space-y-6">
     <div class="flex items-center justify-between">
       <div>
         <h1 class="text-2xl font-bold">{{ modelLabel }}</h1>
         <p class="text-slate-500">Revenue projection using SARIMA model.</p>
       </div>
       <div class="flex items-center space-x-4">
         <label class="text-xs text-gray-500">Year:</label>
         <select v-model="selectedYear" class="border border-gray-300 rounded px-2 py-1 text-sm w-24">
           <option v-for="year in availableYears" :key="year" :value="year">
             {{ year }}
           </option>
         </select>
         <div class="flex items-center space-x-2">
           <label class="text-xs text-gray-500">Month:</label>
           <select v-model="selectedMonth" class="border border-gray-300 rounded px-2 py-1 text-sm w-28">
             <option v-for="(month, index) in monthNames" :key="index" :value="index === 0 ? null : index - 1">
               {{ month }}
             </option>
           </select>
         </div>
        </div>
      </div>

     <UAlert v-if="modelError" type="error" :title="modelError" class="mb-4" />

    <UAlert v-if="isDecemberForecast" type="info" class="mb-4">
      <template #icon>
        <UIcon name="i-lucide-calendar" class="w-5 h-5" />
      </template>
      By December 1st, the model will train and forecast for the next year (January).
    </UAlert>

    <UModal v-model:open="showProgressModal" :dismissible="false">
      <template #header>
        <div class="flex items-center gap-2">
          <UIcon name="i-lucide-brain" class="w-5 h-5 text-primary" />
          <span>Training {{ modelLabel }} Model</span>
        </div>
      </template>
      <template #body>
        <div class="space-y-3">
          <div v-if="modelLoading" class="flex items-center gap-2 text-sm text-slate-500">
            <div class="w-4 h-4 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
            Processing...
          </div>
          <ul class="space-y-2">
            <li v-for="(step, index) in progressSteps" :key="index" class="flex items-start gap-2 text-sm">
              <UIcon v-if="index < progressSteps.length - 1 || !modelLoading" name="i-lucide-check" class="w-4 h-4 text-green-500 mt-0.5" />
              <UIcon v-else name="i-lucide-loader" class="w-4 h-4 text-primary mt-0.5 animate-spin" />
              <span>{{ step }}</span>
            </li>
          </ul>
          <UAlert v-if="progressError" type="error" :title="progressError" class="mt-2" />
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end">
          <UButton v-if="!modelLoading && !progressError" @click="showProgressModal = false">Close</UButton>
          <UButton v-if="progressError" variant="ghost" @click="showProgressModal = false">Dismiss</UButton>
        </div>
      </template>
    </UModal>

    <UModal v-model:open="showForm">
      <template #header>
        {{
          viewing
            ? 'View Forecast'
            : modalMode === 'edit'
              ? 'Edit Forecast'
              : 'New Forecast'
        }}
      </template>
      <template #body>
        <div class="space-y-4">
          <div class="grid gap-4 sm:grid-cols-2">
            <UFormField label="Forecast Date" required>
              <UInput type="date" v-model="form.forecast_date" :disabled="viewing" />
            </UFormField>
            <UFormField label="Predicted Revenue" required>
              <UInput type="number" v-model.number="form.predicted_revenue" :disabled="viewing" />
            </UFormField>
            <UFormField label="Season">
              <UInput v-model="form.season" placeholder="e.g. Rainy, Dry" :disabled="viewing" />
            </UFormField>
            <UFormField label="Model Version">
              <UInput v-model="form.model_version" placeholder="e.g. v1.0" :disabled="viewing" />
            </UFormField>
          </div>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-2">
          <UButton variant="ghost" @click="showForm = false">Close</UButton>
          <UButton v-if="!viewing" @click="submit" :loading="saving">Save</UButton>
        </div>
      </template>
    </UModal>

     <div class="grid gap-6 sm:grid-cols-3">
       <UCard>
         <template #header> Projected Revenue </template>
         <p class="text-2xl font-bold text-primary">{{ currency(totalRevenue) }}</p>
       </UCard>
       <UCard>
          <template #header> {{ selectedMonth === null ? 'Months with Data' : 'Forecast Periods' }} </template>
         <p class="text-2xl font-bold text-primary">{{ periods }}</p>
       </UCard>
       <UCard>
         <template #header> Latest Model </template>
         <p class="text-2xl font-bold text-primary">{{ latestModel }}</p>
       </UCard>
     </div>

     <UTable :data="displayData" :columns="getTableColumns" :pagination-options="paginationOptions" v-model:pagination="tablePagination">
       <template #action-cell="{ row }">
         <UButton
           v-if="can('view forecasts')"
           size="xs"
           color="info"
           variant="ghost"
           @click="openView(row.original)"
           icon="i-lucide-eye"
           class="me-2"
         ></UButton>
         <UButton
           v-if="can('edit forecasts')"
           size="xs"
           @click="openEdit(row.original)"
           icon="i-lucide-edit"
           class="me-2"
         ></UButton>
         <UButton
           v-if="can('delete forecasts')"
           size="xs"
           color="error"
           variant="ghost"
           @click="remove(row.original)"
           icon="i-lucide-trash"
         ></UButton>
       </template>
     </UTable>

     <div class="flex items-center justify-between mt-4">
       <div class="flex items-center gap-2">
         <span class="text-sm text-slate-500">Rows per page:</span>
         <USelect v-model="pageSize" :items="[5, 10, 20, 30, 50]" class="w-20" />
       </div>
       <div class="flex items-center gap-2">
         <span class="text-sm text-slate-500">Go to page:</span>
         <UInput
           v-model="goToPageInput"
           type="number"
           :min="1"
           :max="totalPages"
           class="w-16"
           @keyup.enter="handleGoToPage"
         />
         <UButton size="sm" @click="handleGoToPage">Go</UButton>
       </div>
        <UPagination v-if="selectedMonth !== null" :total="displayData.length" v-model:page="page" :items-per-page="pageSizeNumber" />
     </div>
  </div>
</template>