<script setup lang="ts">
import { apiFetch } from '~/composables/useApiFetch'

definePageMeta({
  layout: "dashboard",
});

const route = useRoute()
const id = route.params.id

const form = reactive({
  fee_name: "",
  base_rate: 0,
  unit: "",
})

onMounted(async () => {
  const item = ((await apiFetch('/v1/fee-types/' + id, { parseJson: true })) as any).data
  Object.assign(form, {
    fee_name: item.fee_name,
    base_rate: item.base_rate,
    unit: item.unit,
  })
})

function save() {
  apiFetch('/v1/fee-types/' + id, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(form),
    parseJson: true,
    throwOnError: true,
  }).then(() => useRouter().push('/cms/fee-types'))
}
</script>

<template>
  <div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-5">Edit Fee Type #{{ id }}</h1>

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

      <div class="flex gap-2 mt-4">
        <UButton type="submit"> Save </UButton>
        <UButton variant="ghost" to="/cms/fee-types"> Cancel </UButton>
      </div>
    </UForm>
  </div>
</template>
