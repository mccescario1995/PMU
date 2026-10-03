<script setup lang="ts">
import { apiFetch } from "~/composables/useApiFetch";
import { useWeatherForecast } from "~/composables/useWeatherForecast";
import { computed, onMounted, ref } from "vue";

definePageMeta({
  layout: "dashboard",
});

const stats = ref({
  today_revenue: 0,
  monthly_revenue: 0,
  yearly_revenue: 0,
  transactions_today: 0,
  active_stakeholders: 0,
  low_stock_items: 0,
  latest_weather: null as any,
});

const { daily: weatherDaily, loading: weatherLoading, error: weatherError } = useWeatherForecast();

const forecastData = ref<any[]>([]);

const nextMonthForecasts = computed(() => {
  const now = new Date()
  let nextMonth = now.getMonth() + 1
  let nextYear = now.getFullYear()
  if (nextMonth > 11) {
    nextMonth = 0
    nextYear++
  }
  const periodStart = new Date(nextYear, nextMonth, 1)
  const periodEnd = new Date(nextYear, nextMonth + 1, 0)
  periodEnd.setHours(23, 59, 59, 999)

  const filtered = forecastData.value.filter((f: any) => {
    const forecastDate = new Date(f.forecast_date)
    return forecastDate >= periodStart && forecastDate <= periodEnd
  })
  console.log('nextMonthForecasts:', filtered.length, filtered.slice(0,3))
  return filtered
})

const maxForecast = computed(() => {
  const vals = nextMonthForecasts.value.map((f) => Number(f.predicted_revenue) || 0)
  const max = Math.max(...vals, 1)
  console.log('maxForecast:', max, 'values:', vals.slice(0,5))
  return max
})

const generating = ref(false);
const generateError = ref("");
const generateSuccess = ref("");

async function generateForecast(model: string, days: number) {
  generating.value = true;
  generateError.value = "";
  generateSuccess.value = "";
  try {
    const res = await apiFetch(`/v1/forecasts/run-model`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ model, days }),
      parseJson: true,
      throwOnError: true,
    }) as any;
    generateSuccess.value = `${model} forecast generated (${res.saved_forecasts?.length || 0} records)`;
    forecastData.value = (await apiFetch("/v1/forecasts/chart", { parseJson: true })) as any[];
  } catch (e: any) {
    generateError.value = e?.body?.message ?? e.message ?? "Failed to generate forecast";
  } finally {
    generating.value = false;
  }
}

onMounted(async () => {
  try {
    stats.value = (await apiFetch("/v1/dashboard", { parseJson: true })) as any;
    forecastData.value = (await apiFetch("/v1/forecasts/chart", { parseJson: true })) as any[];
  } catch {
    // silent
  }
});

const currency = (v: number) =>
  new Intl.NumberFormat("en-PH", { style: "currency", currency: "PHP" }).format(v);

const isToday = (date: string) => {
  const d = new Date(date);
  const t = new Date();
  return d.getFullYear() === t.getFullYear() && d.getMonth() === t.getMonth() && d.getDate() === t.getDate();
};

const formatForecastDate = (dateStr) => {
  if (!dateStr) return ''
  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    timeZone: 'UTC' // Keeps it aligned with the ISO string's UTC offset
  }).format(new Date(dateStr))
}
</script>

<template>
  <div class="space-y-4 w-full">
    <!-- Statistics -->
    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
      <!-- Today's Revenue -->
      <DashboardStatCard icon="i-lucide-philippine-peso" label="Today's Revenue" :value="currency(stats.today_revenue)"
        color="text-success" />
      <!-- Monthly Revenue (last month) -->
      <DashboardStatCard icon="i-lucide-philippine-peso" label="Monthly Revenue" :value="currency(stats.monthly_revenue)"
        color="text-success" />
      <!-- Yearly Revenue (last year) -->
      <DashboardStatCard icon="i-lucide-philippine-peso" label="Yearly Revenue" :value="currency(stats.yearly_revenue)"
        color="text-info" />
      <!-- Transactions Today -->
      <DashboardStatCard icon="i-lucide-receipt" label="Transactions Today" :value="String(stats.transactions_today)" 
        color="text-primary" />
    </div>

    <!-- Revenue Forecast + Weather -->
    <div class="grid gap-6 xl:grid-cols-3">
      <UCard class="xl:col-span-2">
        <template #header>Revenue Forecast</template>
        <div class="flex gap-2">
          <!-- Y-axis labels -->
          <div class="flex flex-col justify-between h-40 w-20 pr-2 border-r border-gray-300">
            <span v-for="i in 5" :key="i" class="text-xs text-gray-500 text-right select-none">
              {{ currency(Math.round(maxForecast * (5 - i) / 4)) }}
            </span>
          </div>
          <!-- Chart bars -->
          <div class="flex-1 flex items-end gap-1 h-40 border-b border-gray-300 pb-1 relative overflow-x-auto min-w-[600px]">
            <div v-for="(item, i) in nextMonthForecasts" :key="i" class="flex-shrink-0 flex flex-col items-center w-8">
              <div class="bg-success/70 hover:bg-success rounded-t w-full cursor-pointer"
                :style="{ height: `${Math.max((Number(item.predicted_revenue) || 0) / maxForecast * 160, 2)}px` }"
                :title="`${new Date(item.forecast_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: '2-digit' })}\nForecasted Revenue: ₱${Number(item.predicted_revenue).toLocaleString()}`" />
              <span class="text-xs text-gray-500 mt-1 whitespace-nowrap" :class="{ 'mt-6': i % 2 === 1 }">
                {{ new Date(item.forecast_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: '2-digit' }) }}
              </span>
            </div>
          </div>
        </div>
      </UCard>

      <UCard>
        <template #header>This Week Weather</template>
        <div v-if="weatherLoading" class="space-y-2">
          <div v-for="i in 4" :key="i" class="h-8 bg-gray-200 rounded animate-pulse" />
        </div>
        <div v-else-if="weatherError" class="text-sm text-red-500">{{ weatherError }}</div>
        <div v-else-if="weatherDaily.length" class="space-y-2">
          <div v-for="d in weatherDaily" :key="d.date"
            :class="['flex items-center justify-between py-1 border-b border-gray-100 last:border-0', isToday(d.date) ? 'font-bold' : '']">
            <span class="text-xs text-slate-500">{{ new Date(d.date).toLocaleDateString("en-US", {
              weekday: "short",
              month: "short", day: "numeric"
            }) }}</span>
            <span class="text-xs font-medium">{{ d.tempMax !== null ? `🌡 ${d.tempMax}°C` : "—" }}</span>
            <span class="text-xs text-slate-400">{{ d.precipitation !== null && d.precipitation > 0 ? `🌧
              ${d.precipitation}mm` : (d.windSpeed !== null ? `💨 ${d.windSpeed}km/h` : "☀") }}</span>
          </div>
        </div>
        <p v-else class="text-sm text-gray-400">No forecast available.</p>
      </UCard>
    </div>

    <!-- Generate Forecast -->
    <!-- <UCard class="mt-6">
      <template #header>
        <div class="flex items-center justify-between">
          <span>Generate Forecast</span>
          <div class="flex gap-2">
            <UButton size="sm" icon="i-lucide-trending-up" :loading="generating"
              @click="generateForecast('linear_regression', 30)">
              Run Linear (30d)
            </UButton>
            <UButton size="sm" icon="i-lucide-brain" :loading="generating" @click="generateForecast('arima', 30)">
              Run ARIMA (30d)
            </UButton>
            <UButton size="sm" icon="i-lucide-wand-2" :loading="generating"
              @click="generateForecast('sarima', 30)">
              Run SARIMA (30d)
            </UButton>
            <UButton size="sm" variant="soft" icon="i-lucide-calendar" :loading="generating"
              @click="generateForecast('sarima', 180)">
              Seasonal (180d)
            </UButton>
          </div>
        </div>
      </template>
      <UAlert v-if="generateError" type="error" :title="generateError" class="mb-2" />
      <UAlert v-if="generateSuccess" type="success" :title="generateSuccess" class="mb-2" />
      <p class="text-sm text-slate-500">
        Click a button to generate a revenue forecast. Forecasts are based on historical transaction data and weather
        conditions. The port manager uses these to plan operations and budget.
      </p>
    </UCard> -->

    <!-- Bottom -->
    <div class="grid gap-6 lg:grid-cols-2 mt-6">
      <DashboardRecentTransactions />
      <DashboardInventoryAlert />
    </div>
  </div>
</template>