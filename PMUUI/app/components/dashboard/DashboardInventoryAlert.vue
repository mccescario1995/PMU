<script setup lang="ts">
import { apiFetch } from "~/composables/useApiFetch";
import { onMounted } from "vue";

const items = ref<
  { id: number; item_name: string; category: string; category_type: string; quantity: number; status: string }[]
>([]);

onMounted(async () => {
  items.value = (await apiFetch("/v1/inventory/items", { parseJson: true })) as any[];
});

const lowStock = computed(() => items.value.filter((i) => i.status === "low_stock" || i.quantity <= 0));
</script>

<template>
  <UCard>
    <template #header>Inventory Alerts</template>
    <div v-if="lowStock.length" class="space-y-3">
      <NuxtLink
        v-for="item in lowStock"
        :key="item.id"
        :to="`/inventory/planning`"
        class="flex items-center justify-between py-2 hover:bg-gray-50 transition rounded-lg px-2"
      >
        <div>
          <p class="text-sm font-medium">{{ item.item_name }}</p>
          <p class="text-xs text-gray-500">{{ item.category_type }} • {{ item.category }}</p>
        </div>
        <UBadge :color="item.quantity <= 0 ? 'error' : 'warning'" variant="subtle">
          {{ item.quantity <= 0 ? 'Out of stock' : `${item.quantity} left` }}
        </UBadge>
      </NuxtLink>
    </div>
    <p v-else class="text-sm text-gray-400">All inventory levels are healthy.</p>
  </UCard>
</template>
