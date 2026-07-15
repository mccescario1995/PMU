<script setup lang="ts">
import { apiFetch } from "~/composables/useApiFetch";
import { onMounted } from "vue";

const forecasts = ref<
  { id: number; forecast_date: string; predicted_revenue: number; season: string | null; model_version: string | null }[]
>([]);

onMounted(async () => {
  forecasts.value = (await apiFetch("/v1/forecasts", { parseJson: true })) as any[];
});

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
        </div>
        <span class="text-sm font-semibold text-success">{{ currency(f.predicted_revenue) }}</span>
      </div>
    </div>
    <p v-else class="text-sm text-gray-400">No forecast data yet.</p>
  </UCard>
</template>
