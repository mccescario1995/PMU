<script setup lang="ts">
const route = useRoute();
const { can } = usePermissions();
const { logout } = useAuth();

const forecastOpen = ref(route.path.startsWith('/forecast'));
const inventoryOpen = ref(route.path.startsWith('/inventory'));

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
    permission: "manage inventory",
    children: [
      {
        title: "Inventory",
        icon: "i-lucide-package",
        to: "/inventory/inventory-list",
        permission: "manage inventory",
      },
      {
        title: "Planning",
        icon: "i-lucide-calendar-check",
        to: "/inventory/planning",
        permission: "manage inventory",
      },
    ],
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
    permission: "view reports",
    children: [
      {
        title: "Linear Regression",
        icon: "i-lucide-trending-up",
        to: "/forecast/linear-regression",
        permission: "view reports",
      },
      {
        title: "AMIRA",
        icon: "i-lucide-brain",
        to: "/forecast/amira",
        permission: "view reports",
      },
      {
        title: "SAMIRA",
        icon: "i-lucide-wand-2",
        to: "/forecast/samira",
        permission: "view reports",
      },
    ],
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
      <template
        v-for="menu in menus.filter((m) => !m.permission || can(m.permission))"
        :key="menu.title"
      >
        <template v-if="menu.title === 'Forecasting' || menu.title === 'Inventory'">
          <button
            @click="menu.title === 'Forecasting' ? forecastOpen = !forecastOpen : inventoryOpen = !inventoryOpen"
            class="flex w-full items-center justify-between gap-4 rounded-xl px-4 py-3 transition"
            :class="[
              (menu.title === 'Forecasting' ? forecastOpen : inventoryOpen)
                ? 'bg-white text-[#17395C] font-semibold'
                : 'hover:bg-white/10',
            ]"
          >
            <span class="flex items-center gap-4">
              <UIcon :name="menu.icon" class="size-5" />
              {{ menu.title }}
            </span>
            <UIcon
              name="i-lucide-chevron-down"
              class="size-4 transition-transform duration-200"
              :class="(menu.title === 'Forecasting' ? forecastOpen : inventoryOpen) ? 'rotate-180' : ''"
            />
          </button>

          <div v-if="menu.title === 'Forecasting' ? forecastOpen : inventoryOpen" class="ml-4 space-y-1">
            <NuxtLink
              v-for="child in menu.children.filter((c: any) => !c.permission || can(c.permission))"
              :key="child.title"
              :to="child.to"
              class="flex items-center gap-4 rounded-xl px-4 py-2 pl-12 text-sm transition"
              :class="[
                route.path.startsWith(child.to)
                  ? 'bg-white text-[#17395C] font-semibold'
                  : 'hover:bg-white/10',
              ]"
            >
              <UIcon :name="child.icon" class="size-4" />
              {{ child.title }}
            </NuxtLink>
          </div>
        </template>

        <template v-else>
          <NuxtLink
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

          <div
            v-if="menu.children && menu.children.length"
            class="ml-4 space-y-1"
          >
            <NuxtLink
              v-for="child in menu.children.filter((c: any) => !c.permission || can(c.permission))"
              :key="child.title"
              :to="child.to"
              class="flex items-center gap-4 rounded-xl px-4 py-2 pl-12 text-sm transition"
              :class="[
                route.path.startsWith(child.to)
                  ? 'bg-white text-[#17395C] font-semibold'
                  : 'hover:bg-white/10',
              ]"
            >
              <UIcon :name="child.icon" class="size-4" />
              {{ child.title }}
            </NuxtLink>
          </div>
        </template>
      </template>
    </nav>

    <!-- Logout -->
    <div class="border-t border-white/20 p-4">
      <UButton block icon="i-lucide-log-out" color="neutral" variant="soft" @click="logout()">
        Logout
      </UButton>
    </div>
  </aside>
</template>
