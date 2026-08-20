<script setup lang="ts">
const route = useRoute()

const forecastOpen = ref(route.path.startsWith('/forecast'))

const menus = [
  {
    title: "Dashboard",
    icon: "i-lucide-layout-dashboard",
    to: "/dashboard",
  },
  {
    title: "Transactions",
    icon: "i-lucide-receipt",
    to: "/transactions",
  },
  {
    title: "Inventory",
    icon: "i-lucide-package",
    to: "/inventory",
    children: [
      {
        title: "Planning",
        icon: "i-lucide-calendar-check",
        to: "/inventory/planning",
      },
    ],
  },
  {
    title: "Stakeholders",
    icon: "i-lucide-users",
    to: "/stakeholders",
  },
  {
    title: "Revenue",
    icon: "i-lucide-philippine-peso",
    to: "/revenue",
  },
  {
    title: "Forecasting",
    icon: "i-lucide-chart-line",
    to: "/forecast",
    children: [
      {
        title: "Linear Regression",
        icon: "i-lucide-trending-up",
        to: "/forecast/linear-regression",
      },
      {
        title: "AMIRA",
        icon: "i-lucide-brain",
        to: "/forecast/amira",
      },
      {
        title: "SAMIRA",
        icon: "i-lucide-wand-2",
        to: "/forecast/samira",
      },
    ],
  },
  {
    title: "Reports",
    icon: "i-lucide-file-bar-chart",
    to: "/reports",
  },
  {
    title: "Accounts",
    icon: "i-lucide-user-cog",
    to: "/accounts",
  },
]
</script>

<template>
  <aside
    class="flex w-72 flex-col bg-[#17395C] text-white"
  >
    <!-- Logo -->

    <div
      class="flex h-24 items-center gap-4 border-b border-white/20 px-6"
    >
      <img
        src="/logo.png"
        class="w-14"
      >

      <div>
        <h2 class="font-bold text-lg">
          PMU PASACAO
        </h2>

        <p class="text-xs text-slate-300">
          Fish Port Operations
        </p>
      </div>
    </div>

    <!-- Menu -->

    <nav class="flex-1 space-y-2 p-4">

      <template
        v-for="menu in menus"
        :key="menu.title"
      >
        <template v-if="menu.title === 'Forecasting'">
          <button
            @click="forecastOpen = !forecastOpen"
            class="flex w-full items-center justify-between gap-4 rounded-xl px-4 py-3 transition"
            :class="[
              route.path.startsWith('/forecast')
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
              :class="forecastOpen ? 'rotate-180' : ''"
            />
          </button>

          <div v-if="forecastOpen" class="ml-4 space-y-1">
            <NuxtLink
              v-for="child in menu.children"
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
              v-for="child in menu.children"
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

      <UButton
        block
        icon="i-lucide-log-out"
        color="neutral"
        variant="soft"
      >
        Logout
      </UButton>

    </div>

  </aside>
</template>