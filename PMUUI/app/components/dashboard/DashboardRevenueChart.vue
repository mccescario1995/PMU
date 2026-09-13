<script setup lang="ts">
import { apiFetch } from "~/composables/useApiFetch";
import { computed, onMounted, ref } from "vue";
import { forecast } from "~/composables/useForecast";

const trend = ref<{ revenue_date: string; total_revenue: number; transaction_count: number }[]>([]);
const forecasts = ref<{ model: string; forecast_data: any[] }[]>([]);
const error = ref<string | null>(null);
const loading = ref(true);

onMounted(async () => {
  try {
    trend.value = (await apiFetch("/v1/dashboard/revenue-trend", { parseJson: true })) as any[];
    forecasts.value = (await forecast()) as any[];
  } catch (e: any) {
    error.value = e.message ?? "Failed to load data";
  } finally {
    loading.value = false;
  }
});

const maxRevenue = computed(() => Math.max(...trend.value.map((t) => Number(t.total_revenue) || 0), 1));
</script>

<template>
  <div v-if="error" class="text-sm text-red-500 p-4">
    Failed to load data: {{ error }}
  </div>
  <div v-else-if="loading" class="text-sm text-gray-400 p-4">
    Loading data...
  </div>
  <div v-else-if="trend.length > 0 && forecasts.length === 0" class="space-y-3">
    <div class="flex items-end gap-1 h-40 border-b border-gray-300 pb-1">
      <div
        v-for="item in trend"
        :key="item.revenue_date"
        class="flex-1 bg-primary/70 hover:bg-primary rounded-t"
        :style="{ height: `${Math.max((Number(item.total_revenue) || 0) / maxRevenue * 160, 2)}px` }"
        :title="`${item.revenue_date}: ₱${(Number(item.total_revenue) || 0).toLocaleString()}`"
      />
    </div>
    <div class="flex justify-between text-xs text-gray-500">
      <span>Historical Trend: {{ trend.length }} data points</span>
      <span>Forecasts: {{ forecasts.length }} models</span>
    </div>
  </div>
  <div v-else-if="trend.length > 0 && forecasts.length > 0" class="space-y-3">
    <div class="flex items-end gap-1 h-40 border-b border-gray-300 pb-1">
      <div
        v-for="item in trend"
        :key="item.revenue_date"
        class="flex-1 bg-primary/70 hover:bg-primary rounded-t"
        :style="{ height: `${Math.max((Number(item.total_revenue) || 0) / maxRevenue * 160, 2)}px` }"
        :title="`${item.revenue_date}: ₱${(Number(item.total_revenue) || 0).toLocaleString()}`"
      />
    </div>
    <div class="flex justify-between text-xs text-gray-500">
      <span>Historical Trend: {{ trend.length }} data points</span>
      <span>Forecasts: {{ forecasts.length }} models</span>
    </div>
  </div>
  <p v-else class="text-sm text-gray-400 p-4">No revenue data available.</p>
</template>
