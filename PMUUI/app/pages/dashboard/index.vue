<script setup lang="ts">
import { apiFetch } from "~/composables/useApiFetch";
import { computed, onMounted, ref } from "vue";

definePageMeta({
  layout: "dashboard",
});

const stats = ref({
  total_revenue: 0,
  transactions_today: 0,
  active_stakeholders: 0,
  low_stock_items: 0,
  latest_weather: null as any,
});

const correlations = ref<{ rainfall: number; temperature: number; wind_speed: number; data_points: number } | null>(null);
const forecastData = ref<any[]>([]);
const maxForecast = computed(() => Math.max(...forecastData.value.map((f) => Number(f.predicted_revenue) || 0), 1));

onMounted(async () => {
  try {
    stats.value = (await apiFetch("/v1/dashboard", { parseJson: true })) as any;
    correlations.value = (await apiFetch("/v1/dashboard/weather-revenue-correlation", { parseJson: true })) as any;
    forecastData.value = (await apiFetch("/v1/forecasts/chart", { parseJson: true })) as any[];
  } catch {
    // silent
  }
});

const currency = (v: number) =>
  new Intl.NumberFormat("en-PH", { style: "currency", currency: "PHP" }).format(v);
</script>

<template>
  <div class="space-y-4 w-full">
    <!-- Statistics -->
    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
      <DashboardStatCard
        icon="i-lucide-philippine-peso"
        label="Total Revenue"
        :value="currency(stats.total_revenue)"
        color="text-success"
      />
      <DashboardStatCard
        icon="i-lucide-receipt"
        label="Transactions Today"
        :value="String(stats.transactions_today)"
        color="text-primary"
      />
      <DashboardStatCard
        icon="i-lucide-users"
        label="Active Stakeholders"
        :value="String(stats.active_stakeholders)"
        color="text-info"
      />
      <DashboardStatCard
        icon="i-lucide-package-x"
        label="Low Stock Items"
        :value="String(stats.low_stock_items)"
        color="text-warning"
      />
    </div>

    <!-- Weather -->
    <div v-if="stats.latest_weather" class="flex items-center gap-4 bg-gray-50 rounded-lg px-4 py-2 ">
      <span class="text-sm text-slate-500">Weather Today: </span>
      <span class="text-sm font-medium">{{ stats.latest_weather.temperature }}°C</span>
      <span class="text-sm text-slate-400">{{ stats.latest_weather.rainfall_mm }}mm</span>
      <span class="text-sm text-slate-400">{{ stats.latest_weather.wind_speed }}km/h</span>
      <span class="text-xs text-slate-300 ml-auto">{{ stats.latest_weather.weather_date }}</span>
    </div>

    <!-- Revenue Forecast -->
    <div class="grid gap-6 xl:grid-cols-3">
      <UCard class="xl:col-span-2">
        <template #header>Revenue Forecast</template>
        <div class="flex items-end gap-1 h-40 border-b border-gray-300 pb-1">
          <div
            v-for="(item, i) in forecastData"
            :key="i"
            class="flex-1 bg-success/70 hover:bg-success rounded-t"
            :style="{ height: `${Math.max((Number(item.predicted_revenue) || 0) / maxForecast * 160, 2)}px` }"
            :title="`${item.period}: ₱${Number(item.predicted_revenue).toLocaleString()}`"
          />
        </div>
        <p class="text-xs text-gray-400 mt-1">{{ forecastData.length }} forecast periods</p>
      </UCard>
      <DashboardForecastCard />
    </div>

    <!-- Weather-Revenue Correlation -->
    <UCard v-if="correlations">
      <template #header>Weather-Revenue Correlation</template>
      <div class="grid gap-4 sm:grid-cols-3">
        <div>
          <p class="text-sm text-slate-500">Rainfall vs Revenue</p>
          <p class="text-xl font-bold">{{ correlations.rainfall }}</p>
        </div>
        <div>
          <p class="text-sm text-slate-500">Temperature vs Revenue</p>
          <p class="text-xl font-bold">{{ correlations.temperature }}</p>
        </div>
        <div>
          <p class="text-sm text-slate-500">Wind Speed vs Revenue</p>
          <p class="text-xl font-bold">{{ correlations.wind_speed }}</p>
        </div>
      </div>
      <p class="text-xs text-slate-400 mt-2">{{ correlations.data_points }} data points</p>
    </UCard>

    <!-- Bottom -->
    <div class="grid gap-6 lg:grid-cols-2 mt-6">
      <DashboardRecentTransactions />
      <DashboardInventoryAlert />
    </div>
  </div>
</template>