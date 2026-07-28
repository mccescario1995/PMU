<script setup lang="ts">
import { apiFetch } from '~/composables/useApiFetch'

definePageMeta({
  layout: "dashboard",
});

const form = reactive({
  fee_name: "",
  base_rate: 0,
  unit: "",
});

function save() {
  apiFetch('/v1/fee-types', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(form),
    parseJson: true,
    throwOnError: true,
  }).then(() => useRouter().push('/cms/fee-types'))
}
</script>

<template>
  <div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-5">Add Fee Type</h1>

    <UForm :state="form" @submit="save">
      <UFormField label="Fee Name">
        <UInput v-model="form.fee_name" />
      </UFormField>

      <UFormField label="Base Rate">
        <UInput v-model="form.base_rate" type="number" step="0.01" />
      </UFormField>

      <UFormField label="Unit">
        <USelect v-model="form.unit" :items="['kg', 'trip', 'day', 'month', 'head', 'item', 'unit', 'transaction', 'hour']" />
      </UFormField>

      <UButton type="submit"> Save </UButton>
    </UForm>
  </div>
</template>
