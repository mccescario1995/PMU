<script setup lang="ts">
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted } from 'vue'
import { useRoute } from 'vue-router'

definePageMeta({
  layout: "dashboard",
});

const currency = (value: number) =>
  new Intl.NumberFormat("en-US", { style: "currency", currency: "PHP" }).format(value)

const route = useRoute();
const id = route.params.id;

const transaction = ref<any>({ id: Number(id), type: "-", stakeholder: "-", amount: 0, date: "-", status: "-", items: [] });

function formatFeeTypes(items: any[]): string {
  if (!items || items.length === 0) return "-";
  if (items.length === 1) return items[0].fee_type?.fee_name ?? "-";
  return items.map((i: any) => i.fee_type?.fee_name).filter(Boolean).join(", ");
}

onMounted(async () => {
  const t = (await apiFetch('/v1/transactions/' + id, { parseJson: true })) as any
  transaction.value = {
    id: t.id,
    type: formatFeeTypes(t.items ?? []),
    stakeholder: t.stakeholder?.name ?? "-",
    amount: t.total_amount,
    date: t.transaction_date,
    status: t.status,
    items: t.items ?? [],
  }
});
</script>

<template>
  <div class="p-6 max-w-2xl space-y-5">
    <UButton icon="i-lucide-arrow-left" variant="ghost" to="/transactions"> Back </UButton>

    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold">{{ transaction.type }}</h1>
        <p class="text-slate-500">Transaction #{{ transaction.id }}</p>
      </div>
      <UButton :to="`/transactions/edit/${transaction.id}`" icon="i-lucide-pencil"> Edit </UButton>
    </div>

    <UCard>
      <template #header> Details </template>
      <dl class="divide-y divide-slate-100">
        <div class="flex justify-between py-2">
          <dt class="text-slate-500">Type(s)</dt>
          <dd class="font-medium">{{ transaction.type }}</dd>
        </div>
        <div class="flex justify-between py-2">
          <dt class="text-slate-500">Stakeholder</dt>
          <dd class="font-medium">{{ transaction.stakeholder }}</dd>
        </div>
        <div class="flex justify-between py-2">
          <dt class="text-slate-500">Amount</dt>
          <dd class="font-semibold text-success">{{ currency(transaction.amount) }}</dd>
        </div>
        <div class="flex justify-between py-2">
          <dt class="text-slate-500">Date</dt>
          <dd class="font-medium">{{ transaction.date }}</dd>
        </div>
        <div class="flex justify-between py-2">
          <dt class="text-slate-500">Status</dt>
          <dd class="font-medium">{{ transaction.status }}</dd>
        </div>
        <div v-if="transaction.items && transaction.items.length > 1" class="flex justify-between py-2">
          <dt class="text-slate-500">Items</dt>
          <dd class="font-medium text-right">
            <div v-for="(item, index) in transaction.items" :key="index" class="text-sm">
              {{ item.fee_type?.fee_name }} × {{ item.quantity }} @ {{ currency(item.unit_price) }} = {{ currency(item.subtotal) }}
            </div>
          </dd>
        </div>
      </dl>
    </UCard>
  </div>
</template>
