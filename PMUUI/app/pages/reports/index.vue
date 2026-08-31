<script setup lang="ts">
definePageMeta({
  layout: "dashboard",
});

const reports = [
  { title: "Daily Report", description: "Transactions and revenue for a specific day.", to: "/reports/daily", icon: "i-lucide-calendar-1" },
  { title: "Monthly Report", description: "Aggregated operations overview per month.", to: "/reports/monthly", icon: "i-lucide-calendar-range" },
  { title: "Yearly Report", description: "Annual trends, totals and forecasts.", to: "/reports/yearly", icon: "i-lucide-calendars" },
]
</script>

<template>
  <div class="p-6 space-y-6">
    <div>
      <h1 class="text-2xl font-bold">Reports</h1>
      <p class="text-slate-500">Generate and review port operation reports.</p>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
      <UCard
        v-for="report in reports"
        :key="report.to"
        class="cursor-pointer transition hover:ring-2 hover:ring-primary"
        :to="report.to"
      >
        <div class="flex items-start gap-4">
          <UIcon :name="report.icon" class="size-8 text-primary" />
          <div>
            <h2 class="font-semibold">{{ report.title }}</h2>
            <p class="text-sm text-slate-500">{{ report.description }}</p>
          </div>
        </div>
      </UCard>
    </div>
  </div>
</template>
