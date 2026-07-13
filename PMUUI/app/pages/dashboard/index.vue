<script setup lang="ts">
definePageMeta({
  layout: "dashboard",
});

const period = ref("Today");
</script>

<template>
  <div class="space-y-8 w-full">
    <!-- Statistics -->
    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
      <DashboardStatCard />
    </div>

    <!-- Charts -->
    <div class="grid gap-6 xl:grid-cols-3 mt-6">
      <UCard class="xl:col-span-2">
        <template #header>...</template>
        <DashboardRevenueChart />
      </UCard>
      <DashboardForecastCard />
    </div>

    <!-- Bottom -->
    <div class="grid gap-6 lg:grid-cols-2 mt-6">
      <DashboardRecentTransactions />
      <DashboardInventoryAlert />
    </div>
  </div>
</template>
