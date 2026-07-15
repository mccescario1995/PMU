<script setup lang="ts">
const route = useRoute();
const { can } = usePermissions();

const menus = [
  {
    title: "Dashboard",
    icon: "i-lucide-layout-dashboard",
    to: "/dashboard",
    permission: null,
  },
  {
    title: "Transactions",
    icon: "i-lucide-receipt",
    to: "/transactions",
    permission: "manage transactions",
  },
  {
    title: "Stakeholders",
    icon: "i-lucide-users",
    to: "/stakeholders",
    permission: "manage stakeholders",
  },
  {
    title: "Inventory",
    icon: "i-lucide-package",
    to: "/inventory",
    permission: "manage inventory",
  },
  {
    title: "Revenue",
    icon: "i-lucide-philippine-peso",
    to: "/revenue",
    permission: "view reports",
  },
  {
    title: "Forecasting",
    icon: "i-lucide-chart-line",
    to: "/forecast",
    permission: "view reports",
  },
  {
    title: "Reports",
    icon: "i-lucide-file-bar-chart",
    to: "/reports",
    permission: "view reports",
  },
  {
    title: "Accounts",
    icon: "i-lucide-user-cog",
    to: "/accounts",
    permission: "manage users",
  },
  {
    title: "CMS",
    icon: "i-lucide-layout-grid",
    to: "/cms",
    permission: "manage settings",
  },
  {
    title: "Audit Logs",
    icon: "i-lucide-scroll-text",
    to: "/audit-logs",
    permission: "manage users",
  },
];
</script>

<template>
  <aside class="flex w-72 flex-col bg-[#17395C] text-white">
    <!-- Logo -->
    <div class="flex h-24 items-center gap-4 border-b border-white/20 px-6">
      <img src="/assets/images/pmu-logo.png" class="w-14" />
      <div>
        <h2 class="font-bold text-lg">PMU PASACAO</h2>
        <p class="text-xs text-slate-300">Fish Port Operations</p>
      </div>
    </div>

    <!-- Menu -->
    <nav class="flex-1 space-y-2 p-4">
      <NuxtLink
        v-for="menu in menus.filter((m) => !m.permission || can(m.permission))"
        :key="menu.title"
        :to="menu.to"
        class="flex items-center gap-4 rounded-xl px-4 py-3 transition"
        :class="[
          route.path.startsWith(menu.to)
            ? 'bg-white text-[#17395C] font-semibold'
            : 'hover:bg-white/10',
        ]"
      >
        <UIcon :name="menu.icon" class="size-5" />
        {{ menu.title }}
      </NuxtLink>
    </nav>

    <!-- Logout -->
    <div class="border-t border-white/20 p-4">
      <UButton block icon="i-lucide-log-out" color="neutral" variant="soft">
        Logout
      </UButton>
    </div>
  </aside>
</template>
