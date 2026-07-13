<script setup lang="ts">
import { apiFetch } from '~/composables/useApiFetch'
definePageMeta({
  layout: "dashboard",
});

const stakeholders = ref([]);
const feeTypes = ref([]);

const form = reactive({
  stakeholder_id: null,
  fee_type_id: null,
  amount: 0,
  transaction_date: "",
  status: "pending",
});

onMounted(async () => {
  stakeholders.value = (await apiFetch('/v1/stakeholders', { parseJson: true })) as any[];
  feeTypes.value = (await apiFetch('/v1/fee-types', { parseJson: true })) as any[];
})

function save() {
  const amount = Number(form.amount) || 0
  const payload = {
    stakeholder_id: form.stakeholder_id,
    transaction_date: form.transaction_date,
    status: form.status,
    remarks: "",
    total_amount: amount,
    items: [
      {
        fee_type_id: form.fee_type_id,
        quantity: 1,
        unit_price: amount,
        subtotal: amount,
      },
    ],
  }
  apiFetch('/v1/transactions', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
    parseJson: true
  }).then(() => useRouter().push('/transactions'))
}
</script>

<template>
  <div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-5">Create Transaction</h1>

    <UForm :state="form" @submit="save">
      <UFormField label="Stakeholder">
        <USelect
          v-model="form.stakeholder_id"
          :items="stakeholders.map(s => ({ label: s.name, value: s.id }))"
          placeholder="Select stakeholder"
        />
      </UFormField>

      <UFormField label="Fee Type">
        <USelect
          v-model="form.fee_type_id"
          :items="feeTypes.map(f => ({ label: f.fee_name, value: f.id }))"
          placeholder="Select fee type"
        />
      </UFormField>

      <UFormField label="Amount">
        <UInput type="number" v-model="form.amount" />
      </UFormField>

      <UFormField label="Date">
        <UInput type="date" v-model="form.transaction_date" />
      </UFormField>

      <UFormField label="Status">
        <USelect v-model="form.status" :items="['pending', 'completed', 'cancelled']" />
      </UFormField>

      <UButton type="submit"> Save </UButton>
    </UForm>
  </div>
</template>
