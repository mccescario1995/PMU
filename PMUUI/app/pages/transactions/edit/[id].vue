<script setup lang="ts">
import { apiFetch } from '~/composables/useApiFetch'
definePageMeta({
  layout: "dashboard",
});

const route = useRoute();
const id = route.params.id;

const stakeholders = ref([]);
const feeTypes = ref([]);

const form = reactive({
  id: Number(id),
  stakeholder_id: null,
  fee_type_id: null,
  amount: 0,
  transaction_date: "",
  status: "pending",
  remarks: "",
});

onMounted(async () => {
  stakeholders.value = (await apiFetch('/v1/stakeholders', { parseJson: true })) as any[]
  feeTypes.value = (await apiFetch('/v1/fee-types', { parseJson: true })) as any[]
  const t = (await apiFetch('/v1/transactions/' + id, { parseJson: true })) as any
  const firstItem = t.items?.[0]
  Object.assign(form, {
    id: t.id,
    stakeholder_id: t.stakeholder_id,
    fee_type_id: firstItem?.fee_type_id ?? null,
    amount: t.total_amount,
    transaction_date: (t.transaction_date ?? "").toString().slice(0, 10),
    status: t.status,
    remarks: t.remarks ?? "",
  })
})

function save() {
  const amount = Number(form.amount) || 0
  const payload = {
    stakeholder_id: form.stakeholder_id,
    transaction_date: form.transaction_date,
    status: form.status,
    remarks: form.remarks,
    total_amount: amount,
  }
  apiFetch('/v1/transactions/' + id, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
    parseJson: true
  }).then(() => useRouter().push('/transactions'))
}
</script>

<template>
  <div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-5">Edit Transaction #{{ form.id }}</h1>

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
