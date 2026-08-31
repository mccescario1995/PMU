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
  form,
  reset,
  submit,
  runModel,
  weatherLabel,
  currency,
  totalRevenue,
  periods,
  latestModel,
  columns,
} = useForecast()

const { page, pageSize, pageSizeNumber, goToPageInput, tablePagination, totalPages, handleGoToPage } = useTablePagination(() => forecasts.value.length)
</script>

<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold">Forecasting</h1>
        <p class="text-slate-500">Projected revenue and volume based on historical operations.</p>
      </div>
      <div class="flex gap-2">
        <UButton
          icon="i-lucide-trending-up"
          :loading="modelLoading"
          @click="runModel('linear_regression')"
        >
          Run Linear
        </UButton>
        <UButton
          icon="i-lucide-brain"
          :loading="modelLoading"
          @click="runModel('amira')"
        >
          Run AMIRA
        </UButton>
        <UButton
          icon="i-lucide-wand-2"
          :loading="modelLoading"
          @click="runModel('samira')"
        >
          Run SAMIRA
        </UButton>
        <UButton icon="i-lucide-plus" @click="showForm = true; reset()"> Add Forecast </UButton>
      </div>
    </div>

    <UAlert v-if="modelError" type="error" :title="modelError" class="mb-4" />

    <UCard v-if="showForm">
      <template #header> New Forecast </template>
      <UForm @submit="submit">
        <div class="grid gap-4 sm:grid-cols-2">
          <UFormField label="Forecast Date" required>
            <UInput type="date" v-model="form.forecast_date" />
          </UFormField>
          <UFormField label="Predicted Revenue" required>
            <UInput type="number" v-model.number="form.predicted_revenue" />
          </UFormField>
          <UFormField label="Season">
            <UInput v-model="form.season" placeholder="e.g. Rainy, Dry" />
          </UFormField>
          <UFormField label="Model Version">
            <UInput v-model="form.model_version" placeholder="e.g. v1.0" />
          </UFormField>
          <UFormField label="Weather on Date">
            <p class="text-sm text-gray-500 py-2">{{ weatherLabel(form.weather) }}</p>
          </UFormField>
        </div>
        <div class="flex gap-2 mt-4">
          <UButton type="submit"> Save </UButton>
          <UButton variant="ghost" @click="reset"> Cancel </UButton>
        </div>
      </UForm>
    </UCard>

    <div class="grid gap-6 sm:grid-cols-3">
      <UCard>
        <template #header> Projected Revenue </template>
        <p class="text-2xl font-bold text-primary">{{ currency(totalRevenue) }}</p>
      </UCard>
      <UCard>
        <template #header> Forecast Periods </template>
        <p class="text-2xl font-bold text-primary">{{ periods }}</p>
      </UCard>
      <UCard>
        <template #header> Latest Model </template>
        <p class="text-2xl font-bold text-primary">{{ latestModel }}</p>
      </UCard>
    </div>

    <UTable :data="forecasts" :columns="columns" :pagination-options="{ getPaginationRowModel: getPaginationRowModel() }" v-model:pagination="tablePagination" />

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
      <UPagination :total="forecasts.length" v-model:page="page" :items-per-page="pageSizeNumber" />
    </div>
  </div>
</template>
