<script setup lang="ts">
definePageMeta({
  layout: "dashboard",
});

const modules = [
  { title: "Accounts", description: "Manage users, roles and access.", icon: "i-lucide-user-cog", to: "/cms/accounts", color: "text-blue-500" },
  { title: "Stakeholders", description: "Buyers, brokers and renters.", icon: "i-lucide-users", to: "/cms/stakeholders", color: "text-green-500" },
  { title: "Transactions", description: "Fee collection and payment records.", icon: "i-lucide-receipt", to: "/cms/transactions", color: "text-orange-500" },
  { title: "Inventory", description: "Items, stock levels and adjustments.", icon: "i-lucide-package", to: "/cms/inventory", color: "text-purple-500" },
  { title: "Reports", description: "Daily, monthly and yearly summaries.", icon: "i-lucide-file-bar-chart", to: "/cms/reports", color: "text-indigo-500" },
  { title: "Fee Types", description: "Configure port fee rates and units.", icon: "i-lucide-tag", to: "/cms/fee-types", color: "text-yellow-600" },
  { title: "Stakeholder Types", description: "Manage stakeholder classifications.", icon: "i-lucide-users-2", to: "/cms/stakeholder-types", color: "text-teal-500" },
  { title: "Weather Data", description: "Environmental condition logs.", icon: "i-lucide-cloud", to: "/cms/weather", color: "text-cyan-500" },
  { title: "Import Logs", description: "Review import history and errors.", icon: "i-lucide-upload", to: "/cms/imports", color: "text-lime-500" },
  { title: "Settings", description: "App-wide configuration values.", icon: "i-lucide-gear", to: "/cms/settings", color: "text-gray-500" },
  { title: "Statuses", description: "Manage inventory, transaction and stakeholder statuses.", icon: "i-lucide-circle-dot", to: "/cms/statuses", color: "text-pink-500" },
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
        @click="navigateTo(mod.to)"
        class="cursor-pointer transition hover:ring-2 hover:ring-primary/50"
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
