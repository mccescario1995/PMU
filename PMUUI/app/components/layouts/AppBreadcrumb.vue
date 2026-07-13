<script setup lang="ts">
const route = useRoute();

const labels: Record<string, string> = {
  dashboard: "Dashboard",
  transactions: "Transactions",
  stakeholders: "Stakeholders",
  inventory: "Inventory",
  revenue: "Revenue",
  forecast: "Forecasting",
  reports: "Reports",
  accounts: "Accounts",
  settings: "Settings",
  login: "Login",
  create: "Create",
  edit: "Edit",
  categories: "Categories",
  stocks: "Stocks",
  buyers: "Buyers",
  brokers: "Brokers",
  daily: "Daily",
  monthly: "Monthly",
  yearly: "Yearly",
  details: "Details",
};

const crumbs = computed(() => {
  const pathSegments = route.path.split("/").filter(Boolean);
  const templateSegments = (route.matched.at(-1)?.path ?? route.path)
    .split("/")
    .filter(Boolean);

  const result: { label: string; to: string }[] = [];
  let accumulated = "";

  pathSegments.forEach((seg, i) => {
    accumulated += `/${seg}`;
    const tmpl = templateSegments[i] ?? seg;

    let label: string;
    if (tmpl.startsWith(":")) {
      label = (route.params[tmpl.slice(1)] as string) ?? seg;
    } else {
      label = labels[tmpl] ?? tmpl.charAt(0).toUpperCase() + tmpl.slice(1);
    }

    result.push({ label, to: accumulated });
  });

  return result;
});
</script>

<template>
  <nav class="text-sm text-slate-500">
    <ul class="flex flex-wrap items-center gap-1.5">
      <li
        v-for="(crumb, index) in crumbs"
        :key="crumb.to"
        class="flex items-center gap-1.5"
      >
        <span v-if="index">/</span>

        <NuxtLink
          v-if="index < crumbs.length - 1"
          :to="crumb.to"
          class="transition hover:text-slate-800 hover:underline"
        >
          {{ crumb.label }}
        </NuxtLink>

        <span
          v-else
          class="font-medium text-slate-800"
          :aria-current="crumbs.length ? 'page' : undefined"
        >
          {{ crumb.label }}
        </span>
      </li>
    </ul>
  </nav>
</template>
