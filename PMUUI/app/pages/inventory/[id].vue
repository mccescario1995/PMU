<script setup lang="ts">
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted } from 'vue'
import { usePermissions } from '~/composables/usePermissions'

definePageMeta({
  layout: "dashboard",
});

const route = useRoute()
const id = route.params.id
const { can } = usePermissions()

const UBadge = resolveComponent('UBadge')

const item = ref<any>(null)
const itemLogs = ref<any[]>([])
const loading = ref(true)
const showAddStock = ref(false)
const showDeductStock = ref(false)
const stockQty = ref(1)

onMounted(async () => {
  loading.value = true
  item.value = (await apiFetch(`/v1/inventory/items/${id}`, { parseJson: true })).data
  itemLogs.value = (await apiFetch(`/v1/inventory/items/${id}/logs`, { parseJson: true })) as any[]
  loading.value = false
})

async function remove() {
  if (!confirm('Delete this inventory item?')) return
  await apiFetch(`/v1/inventory/items/${id}`, { method: 'DELETE' })
  useRouter().push('/inventory')
}

async function submitAddStock() {
  await apiFetch(`/v1/inventory/items/${id}/add-stock`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ quantity: stockQty.value }),
    parseJson: true,
    throwOnError: true,
  })
  showAddStock.value = false
  stockQty.value = 1
  item.value = (await apiFetch(`/v1/inventory/items/${id}`, { parseJson: true })).data
  itemLogs.value = ((await apiFetch(`/v1/inventory/items/${id}/logs`, { parseJson: true })) as any[]).data
}

async function submitDeductStock() {
  await apiFetch(`/v1/inventory/items/${id}/deduct-stock`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ quantity: stockQty.value }),
    parseJson: true,
    throwOnError: true,
  })
  showDeductStock.value = false
  stockQty.value = 1
  item.value = (await apiFetch(`/v1/inventory/items/${id}`, { parseJson: true })).data
  itemLogs.value = (await apiFetch(`/v1/inventory/items/${id}/logs`, { parseJson: true })) as any[]
}
</script>

<template>
  <div class="p-6 space-y-5">
    <UButton icon="i-lucide-arrow-left" variant="ghost" to="/inventory"> Back </UButton>

    <div v-if="item" class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold">{{ item.item_name }}</h1>
          <p class="text-slate-500">Inventory Item #{{ item.id }}</p>
        </div>
        <div class="flex gap-2">
          <UButton v-if="can('manage inventory')" :to="`/inventory/edit/${item.id}`"  icon="i-lucide-edit" > Edit </UButton>
          <UButton v-if="can('manage inventory')" color="error" variant="ghost"  @click="remove" icon="i-lucide-trash" > Delete </UButton>
        </div>
      </div>

      <div class="grid gap-4 sm:grid-cols-4">
        <UCard>
          <template #header> Category Type </template>
          <UBadge :color="item.category_type === 'equipment' ? 'primary' : item.category_type === 'materials' ? 'success' : 'warning'" variant="subtle" class="capitalize">
            {{ item.category_type }}
          </UBadge>
        </UCard>
        <UCard>
          <template #header> Category </template>
          <p class="text-xl font-semibold">{{ item.category }}</p>
        </UCard>
        <UCard>
          <template #header> Quantity </template>
          <p class="text-xl font-semibold">{{ item.quantity }} {{ item.unit }}</p>
        </UCard>
        <UCard>
          <template #header> Status </template>
          <UBadge :color="item.status === 'available' ? 'success' : item.status === 'low_stock' ? 'warning' : 'error'" variant="subtle" class="capitalize">
            {{ item.status }}
          </UBadge>
        </UCard>
      </div>

      <div v-if="can('manage inventory')" class="flex gap-2">
        <UButton v-if="!showAddStock" icon="i-lucide-plus" @click="showAddStock = true"> Add Stock </UButton>
        <UButton v-if="!showDeductStock" color="error" variant="outline" icon="i-lucide-minus" @click="showDeductStock = true"> Deduct Stock </UButton>
      </div>

      <UCard v-if="showAddStock">
        <template #header> Add Stock </template>
        <UForm @submit="submitAddStock">
          <UFormField label="Quantity" required>
            <UInput type="number" v-model="stockQty" :min="1" />
          </UFormField>
          <div class="flex gap-2 mt-4">
            <UButton type="submit"> Confirm </UButton>
            <UButton variant="ghost" @click="showAddStock = false"> Cancel </UButton>
          </div>
        </UForm>
      </UCard>

      <UCard v-if="showDeductStock">
        <template #header> Deduct Stock </template>
        <UForm @submit="submitDeductStock">
          <UFormField label="Quantity" required>
            <UInput type="number" v-model="stockQty" :min="1" />
          </UFormField>
          <div class="flex gap-2 mt-4">
            <UButton type="submit" color="error"> Confirm </UButton>
            <UButton variant="ghost" @click="showDeductStock = false"> Cancel </UButton>
          </div>
        </UForm>
      </UCard>

      <UCard>
        <template #header> Stock Movement Logs </template>
        <UTable :data="itemLogs" :columns="[
          { accessorKey: 'id', header: '#', cell: ({ row }) => `#${row.getValue('id')}` },
          { accessorKey: 'action', header: 'Action', cell: ({ row }) => {
            const action = row.getValue('action')
            const color = action === 'add' ? 'success' : action === 'deduct' ? 'error' : 'warning'
            return h(UBadge, { color, variant: 'subtle' }, () => action)
          }},
          { accessorKey: 'quantity_changed', header: 'Qty Changed', meta: { class: { th: 'text-right', td: 'text-right font-mono' } } },
          { accessorKey: 'user', header: 'User', cell: ({ row }) => row.original.user?.name ?? '-' },
          { accessorKey: 'created_at', header: 'Date', cell: ({ row }) => new Date(row.original.created_at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) },
        ]">
        </UTable>
      </UCard>
    </div>

    <p v-else class="text-sm text-gray-400">Loading item details...</p>
  </div>
</template>
