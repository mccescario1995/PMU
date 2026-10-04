<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { useForecast } from '~/composables/useForecast'
import { computed, watch } from 'vue'
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
    runSarimaDirect,
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

// Year and month selection controls
const selectedYear = ref(new Date().getFullYear())
const selectedMonth = ref(null) // null = all months, 0-11 = specific month

// Available years from forecast data
const availableYears = computed(() => {
  const years = new Set<number>();
  if (forecasts.value && Array.isArray(forecasts.value)) {
    forecasts.value.forEach(f => {
      if (f && f.forecast_date) {
        const date = new Date(f.forecast_date);
        if (!isNaN(date.getTime())) {
          years.add(date.getFullYear());
        }
      }
    });
  }
  return Array.from(years).sort();
});

// Month names for display
const monthNames = ["All Months", "Jan", "Feb", "Mar", "Apr", "May", "Jun", 
                   "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"]
// Note: index 0 = "All Months", indices 1-12 = Jan-Dec (so month value + 1 for array index)

// Filtered forecasts based on model type
const filteredForecasts = computed(() => {
  if (!forecasts.value || !Array.isArray(forecasts.value)) {
    return [];
  }
  return forecasts.value.filter((f: any) => {
    if (!f || !f.model_version) return false;
    const mv = f.model_version.toLowerCase();
    const slug = model.replace(/_/g, '-');
    return mv.includes(slug) || mv.includes(model);
  });
});

// Forecasts for selected year (and optionally month)
const selectedForecasts = computed(() => {
  const year = selectedYear.value;
  const month = selectedMonth.value; // null = all months, 0-11 = Jan-Dec

  // Filter by year first
  let yearFiltered = [];
  if (filteredForecasts.value && Array.isArray(filteredForecasts.value)) {
    yearFiltered = filteredForecasts.value.filter((f: any) => {
      if (!f || !f.forecast_date) return false;
      const forecastDate = new Date(f.forecast_date);
      if (isNaN(forecastDate.getTime())) return false;
      return forecastDate.getFullYear() === year;
    });
  }
  
  // If specific month selected, filter by month too
  if (month !== null) {
    yearFiltered = yearFiltered.filter((f: any) => {
      if (!f || !f.forecast_date) return false;
      const forecastDate = new Date(f.forecast_date);
      if (isNaN(forecastDate.getTime())) return false;
      return forecastDate.getMonth() === month;
    });
  }
  
  return yearFiltered;
});

// Group forecasts by month when showing all months
const monthlyAggregates = computed(() => {
    if (selectedMonth.value !== null) {
        // Showing specific month, no need for monthly aggregates
        return []
    }
    
    const year = selectedYear.value
    const monthlyData = Array(12).fill(null).map((_, monthIndex) => ({
        month: monthIndex,
        totalRevenue: 0,
        count: 0,
        latestModel: "-"
    }))
    
    if (selectedForecasts.value && Array.isArray(selectedForecasts.value)) {
      selectedForecasts.value.forEach(f => {
        if (!f || !f.forecast_date) return;
        const forecastDate = new Date(f.forecast_date)
        if (isNaN(forecastDate.getTime())) return;
        if (forecastDate.getFullYear() === year) {
            const monthIndex = forecastDate.getMonth()
            const revenue = Number(f.predicted_revenue ?? 0)
            monthlyData[monthIndex].totalRevenue += revenue
            monthlyData[monthIndex].count += 1
            // Keep the latest model version (simply take the last one encountered)
            monthlyData[monthIndex].latestModel = f.model_version ?? "-"
        }
      })
    }
    
    return monthlyData.map((data, index) => ({
        month: index,
        monthName: monthNames[index + 1], // +1 because index 0 is "All Months"
        totalRevenue: data.totalRevenue,
        count: data.count,
        latestModel: data.latestModel
    })).filter(data => data.count > 0) // Only include months with data
})

// Determine what to display in table
const displayData = computed(() => {
    if (selectedMonth.value === null) {
        // Showing all months - return monthly aggregates
        return monthlyAggregates.value || []
    } else {
        // Showing specific month - return daily forecasts
        return selectedForecasts.value || []
    }
})

const isDecemberForecast = computed(() => {
    const now = new Date()
    let nextMonth = now.getMonth() + 1
    if (nextMonth > 11) nextMonth = 0
    return nextMonth === 11
})

// Statistics based on what's being displayed
const totalRevenue = computed(() => {
    if (selectedMonth.value === null) {
        // Showing all months - yearly total
        if (!monthlyAggregates.value || !Array.isArray(monthlyAggregates.value)) {
          return 0
        }
        return monthlyAggregates.value.reduce((sum, m) => sum + m.totalRevenue, 0)
    } else {
        // Showing specific month - monthly total
        if (!selectedForecasts.value || !Array.isArray(selectedForecasts.value)) {
          return 0
        }
        return selectedForecasts.value.reduce((sum, f) => sum + Number(f.predicted_revenue ?? 0), 0)
    }
})

const periods = computed(() => {
    if (selectedMonth.value === null) {
        // Showing all months - number of months with data
        if (!monthlyAggregates.value || !Array.isArray(monthlyAggregates.value)) {
          return 0
        }
        return monthlyAggregates.value.length
    } else {
        // Showing specific month - number of days
        if (!selectedForecasts.value || !Array.isArray(selectedForecasts.value)) {
          return 0
        }
        return selectedForecasts.value.length
    }
})

const latestModel = computed(() => {
    if (selectedMonth.value === null) {
        // Showing all months - show latest model from yearly data
        if (!selectedForecasts.value || !Array.isArray(selectedForecasts.value) || selectedForecasts.value.length === 0) {
          return "-"
        }
        return selectedForecasts.value[0]?.model_version ?? "-"
    } else {
        // Showing specific month - show latest model from monthly data
        if (!selectedForecasts.value || !Array.isArray(selectedForecasts.value) || selectedForecasts.value.length === 0) {
          return "-"
        }
        return selectedForecasts.value[0]?.model_version ?? "-"
    }
})

// Table columns based on what's being displayed
const getTableColumns = computed(() => {
    if (selectedMonth.value === null) {
        // Showing monthly aggregates
        return [
            {
                header: 'Month',
                accessorKey: 'monthName'
            },
            {
                header: 'Total Revenue',
                accessorKey: 'totalRevenue'
            },
            {
                header: 'Days with Data',
                accessorKey: 'count'
            },
            {
                header: 'Latest Model',
                accessorKey: 'latestModel'
            }
        ]
    } else {
        // Showing daily forecasts - use original columns
        return columns
    }
})

const { page, pageSize, pageSizeNumber, goToPageInput, tablePagination, totalPages, handleGoToPage } = useTablePagination(() => 
    selectedMonth.value === null ? 0 : (selectedForecasts.value || []).length
)

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
       <div class="flex gap-2">
         <UButton
           icon="i-lucide-brain"
           :loading="modelLoading"
           @click="runSarimaDirect"
         >
           Run {{ modelLabel }}
         </UButton>
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
         <template #header> {{ selectedMonth.value === null ? 'Months with Data' : 'Forecast Periods' }} </template>
         <p class="text-2xl font-bold text-primary">{{ periods }}</p>
       </UCard>
       <UCard>
         <template #header> Latest Model </template>
         <p class="text-2xl font-bold text-primary">{{ latestModel }}</p>
       </UCard>
     </div>

     <UTable :data="displayData" :columns="getTableColumns" :pagination-options="{ getPaginationRowModel: getPaginationRowModel() }" v-model:pagination="tablePagination">
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
        <UPagination v-if="selectedMonth.value !== null" :total="(selectedForecasts.value || []).length" v-model:page="page" :items-per-page="pageSizeNumber" />
     </div>
  </div>
</template>