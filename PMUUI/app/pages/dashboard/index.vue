<script setup lang="ts">
import { apiFetch } from "~/composables/useApiFetch";
import { onMounted } from "vue";

definePageMeta({
  layout: "dashboard",
});

const stats = ref({
  total_revenue: 0,
  transactions_today: 0,
  active_stakeholders: 0,
  low_stock_items: 0,
});

const correlations = ref<{ rainfall: number; temperature: number; wind_speed: number } | null>(null)

onMounted(async () => {
  stats.value = (await apiFetch("/v1/dashboard", { parseJson: true })) as any;
  correlations.value = (await apiFetch("/v1/dashboard/weather-revenue-correlation", { parseJson: true })) as any;
});

const currency = (v: number) =>
  new Intl.NumberFormat("en-PH", { style: "currency", currency: "PHP" }).format(v);
</script>

<template>
  <div class="space-y-8 w-full">
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
        icon="i-lucide-package-alert"
        label="Low Stock Items"
        :value="String(stats.low_stock_items)"
        color="text-warning"
      />
    </div>

    <!-- Charts -->
    <div class="grid gap-6 xl:grid-cols-3 mt-6">
      <UCard class="xl:col-span-2">
        <template #header>Revenue Trend</template>
        <DashboardRevenueChart />
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
    </UCard>

    <!-- Bottom -->
    <div class="grid gap-6 lg:grid-cols-2 mt-6">
      <DashboardRecentTransactions />
      <DashboardInventoryAlert />
    </div>
  </div>
</template>