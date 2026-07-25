<script setup lang="ts">
import { apiFetch } from "~/composables/useApiFetch";
import { onMounted } from "vue";

const forecasts = ref<
  { id: number; forecast_date: string; predicted_revenue: number; season: string | null; model_version: string | null; weather: any }
[]>([]);

onMounted(async () => {
  forecasts.value = (await apiFetch("/v1/forecasts", { parseJson: true })) as any[];
});

const weatherLabel = (w: any) => {
  if (!w) return "No weather data";
  const parts: string[] = [];
  if (w.rainfall_mm !== null && w.rainfall_mm !== undefined) parts.push(`${w.rainfall_mm}mm rain`);
  if (w.temperature !== null && w.temperature !== undefined) parts.push(`${w.temperature}°C`);
  if (w.wind_speed !== null && w.wind_speed !== undefined) parts.push(`${w.wind_speed}km/h wind`);
  return parts.length ? parts.join(', ') : "No weather data";
};

const currency = (v: number) =>
  new Intl.NumberFormat("en-PH", { style: "currency", currency: "PHP" }).format(v);
</script>

<template>
  <UCard>
    <template #header>Revenue Forecast</template>
    <div v-if="forecasts.length" class="space-y-4">
      <div v-for="f in forecasts.slice(0, 4)" :key="f.id" class="flex items-center justify-between py-2 border-b last:border-0">
        <div>
          <p class="text-sm font-medium">{{ new Date(f.forecast_date).toLocaleDateString("en-US", { month: "short", year: "numeric" }) }}</p>
          <p class="text-xs text-gray-500">v{{ f.model_version || "—" }} {{ f.season ? `• ${f.season}` : "" }}</p>
          <p class="text-xs text-info">🌤 {{ weatherLabel(f.weather) }}</p>
        </div>
        <span class="text-sm font-semibold text-success">{{ currency(f.predicted_revenue) }}</span>
      </div>
    </div>
    <p v-else class="text-sm text-gray-400">No forecast data yet.</p>
  </UCard>
</template>