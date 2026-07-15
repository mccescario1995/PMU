<script setup lang="ts">
import { apiFetch } from "~/composables/useApiFetch";
import { onMounted } from "vue";

const trend = ref<{ revenue_date: string; total_revenue: number; transaction_count: number }[]>([]);

onMounted(async () => {
  trend.value = (await apiFetch("/v1/dashboard/revenue-trend", { parseJson: true })) as any[];
});

const maxRevenue = computed(() => Math.max(...trend.value.map((t) => t.total_revenue), 1));
</script>

<template>
  <div v-if="trend.length" class="space-y-4">
    <div class="flex items-end gap-2 h-48">
      <div
        v-for="item in trend"
        :key="item.revenue_date"
        class="flex-1 flex flex-col items-center gap-1"
      >
        <div
          class="w-full max-w-[3rem] bg-primary/80 rounded-t-md transition-all hover:bg-primary"
          :style="{ height: `${(item.total_revenue / maxRevenue) * 100}%` }"
          :title="`${item.revenue_date}: ₱${item.total_revenue.toLocaleString()}`"
        />
      </div>
    </div>
    <div class="flex gap-2 overflow-x-auto text-xs text-gray-500">
      <div v-for="item in trend" :key="item.revenue_date" class="flex-1 text-center">
        {{ new Date(item.revenue_date).toLocaleDateString("en-US", { month: "short", day: "numeric" }) }}
      </div>
    </div>
  </div>
  <p v-else class="text-sm text-gray-400">No revenue data available.</p>
</template>
