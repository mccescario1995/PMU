<script setup lang="ts">
import { apiFetch } from '~/composables/useApiFetch'

definePageMeta({
  layout: "dashboard",
});

const route = useRoute()
const id = route.params.id

const form = reactive({
  item_name: "",
  category: "",
  category_type: "supplies",
  quantity: 0,
  unit: "pcs",
  status: "available",
})

onMounted(async () => {
  const item = await apiFetch(`/v1/inventory/inventory-list/items/${id}`, { parseJson: true })
  Object.assign(form, {
    item_name: item.data.item_name,
    category: item.data.category,
    category_type: item.data.category_type ?? "supplies",
    quantity: item.data.quantity,
    unit: item.data.unit ?? "pcs",
    status: item.data.status,
  })
})

function save() {
  apiFetch(`/v1/inventory/inventory-list/items/${id}`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(form),
    parseJson: true,
    throwOnError: true,
  }).then(() => useRouter().push('/inventory/inventory-list'))
}
</script>

<template>
  <div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-5">Edit Inventory Item #{{ id }}</h1>

    <UForm :state="form" @submit="save">
      <UFormField label="Item Name">
        <UInput v-model="form.item_name" />
      </UFormField>

      <UFormField label="Category">
        <UInput v-model="form.category" />
      </UFormField>

      <UFormField label="Category Type">
        <USelect
          v-model="form.category_type"
          :items="['equipment', 'materials', 'supplies']"
        />
      </UFormField>

      <UFormField label="Quantity">
        <UInput type="number" v-model="form.quantity" />
      </UFormField>

      <UFormField label="Unit">
        <UInput v-model="form.unit" />
      </UFormField>

      <UFormField label="Status">
        <USelect
          v-model="form.status"
          :items="['available', 'low_stock', 'damaged']"
        />
      </UFormField>

      <div class="flex gap-2 mt-4">
        <UButton type="submit"> Save </UButton>
        <UButton variant="ghost" to="/inventory/inventory-list"> Cancel </UButton>
      </div>
    </UForm>
  </div>
</template>
