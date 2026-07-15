<script setup lang="ts">
import { apiFetch } from "~/composables/useApiFetch";
import { onMounted } from "vue";

const transactions = ref<
  {
    id: number;
    stakeholder: { id: number; name: string } | null;
    stakeholder_id: number;
    total_amount: number;
    transaction_date: string;
    status: string;
  }[]
>([]);

onMounted(async () => {
  transactions.value = (await apiFetch("/v1/transactions", { parseJson: true })) as any[];
});

const currency = (v: number) =>
  new Intl.NumberFormat("en-PH", { style: "currency", currency: "PHP" }).format(v);
</script>

<template>
  <UCard>
    <template #header>Recent Transactions</template>
    <div v-if="transactions.length" class="divide-y">
      <NuxtLink
        v-for="tx in transactions.slice(0, 5)"
        :key="tx.id"
        :to="`/transactions/${tx.id}`"
        class="flex items-center justify-between py-3 hover:bg-gray-50 transition"
      >
        <div>
          <p class="text-sm font-medium">
            {{ tx.stakeholder?.name ?? `#${tx.stakeholder_id}` }}
          </p>
          <p class="text-xs text-gray-500">
            {{ new Date(tx.transaction_date).toLocaleDateString("en-US", { month: "short", day: "numeric" }) }}
          </p>
        </div>
        <div class="text-right">
          <p class="text-sm font-semibold">{{ currency(tx.total_amount) }}</p>
          <p class="text-xs" :class="tx.status === 'completed' ? 'text-green-600' : tx.status === 'pending' ? 'text-yellow-600' : 'text-red-600'">
            {{ tx.status }}
          </p>
        </div>
      </NuxtLink>
    </div>
    <p v-else class="text-sm text-gray-400">No transactions yet.</p>
  </UCard>
</template>
