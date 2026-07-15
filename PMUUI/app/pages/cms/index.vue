<script setup lang="ts">
definePageMeta({
  layout: "dashboard",
});

const modules = [
  { title: "Accounts", description: "Manage users, roles and access.", icon: "i-lucide-user-cog", to: "/accounts", color: "text-blue-500" },
  { title: "Stakeholders", description: "Buyers, brokers and renters.", icon: "i-lucide-users", to: "/stakeholders", color: "text-green-500" },
  { title: "Transactions", description: "Fee collection and payment records.", icon: "i-lucide-receipt", to: "/transactions", color: "text-orange-500" },
  { title: "Inventory", description: "Items, stock levels and adjustments.", icon: "i-lucide-package", to: "/inventory", color: "text-purple-500" },
  { title: "Fee Types", description: "Configure port fee rates and units.", icon: "i-lucide-tag", to: "/fee-types", color: "text-yellow-600" },
  { title: "Weather Data", description: "Environmental condition logs.", icon: "i-lucide-cloud", to: "/weather", color: "text-cyan-500" },
  { title: "Audit Logs", description: "System change tracking.", icon: "i-lucide-scroll-text", to: "/audit-logs", color: "text-gray-500" },
  { title: "Inventory Logs", description: "Stock movement history.", icon: "i-lucide-clipboard-list", to: "/inventory/logs", color: "text-pink-500" },
  { title: "Reports", description: "Daily, monthly and yearly summaries.", icon: "i-lucide-file-bar-chart", to: "/reports", color: "text-indigo-500" },
]
</script>

<template>
  <div class="p-6 space-y-6">
    <div>
      <h1 class="text-2xl font-bold">Admin CMS</h1>
      <p class="text-slate-500">Manage all port operation data from one place.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <UCard
        v-for="mod in modules"
        :key="mod.to"
        class="cursor-pointer transition hover:ring-2 hover:ring-primary/50"
        :to="mod.to"
      >
        <div class="flex items-start gap-4">
          <UIcon :name="mod.icon" class="size-8 mt-1" :class="mod.color" />
          <div>
            <h2 class="font-semibold">{{ mod.title }}</h2>
            <p class="text-sm text-slate-500 mt-1">{{ mod.description }}</p>
          </div>
        </div>
      </UCard>
    </div>
  </div>
</template>
