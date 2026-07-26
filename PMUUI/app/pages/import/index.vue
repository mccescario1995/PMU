<script setup lang="ts">
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted } from 'vue'

definePageMeta({
  layout: "dashboard",
});

const entityType = ref('revenue_histories')
const file = ref<File | null>(null)
const logs = ref<any[]>([])
const uploading = ref(false)

const entityOptions = [
  { label: 'Revenue Histories', value: 'revenue_histories' },
  { label: 'Weather Data', value: 'weather_data' },
  { label: 'Stakeholders', value: 'stakeholders' },
]

function onFileChange(e: Event) {
  const target = e.target as HTMLInputElement
  file.value = target.files?.[0] ?? null
}

async function submit() {
  if (!file.value) return

  uploading.value = true

  const form = new FormData()
  form.append('entity_type', entityType.value)
  form.append('file', file.value)

  try {
    await apiFetch('/v1/imports', {
      method: 'POST',
      body: form,
      parseJson: true,
    })
    file.value = null
    await load()
  } finally {
    uploading.value = false
  }
}

async function load() {
  logs.value = (await apiFetch('/v1/imports', { parseJson: true })) as any[]
}

onMounted(load)
</script>

<template>
  <div class="p-6 space-y-6">
    <div>
      <h1 class="text-2xl font-bold">Historical Data Import</h1>
      <p class="text-slate-500">Import CSV or Excel (.xlsx) logbooks into the system.</p>
    </div>

    <UCard>
      <template #header> Upload File </template>
      <UForm @submit="submit">
        <div class="grid gap-4 sm:grid-cols-2">
          <UFormField label="Entity Type" required>
            <USelect v-model="entityType" :options="entityOptions" />
          </UFormField>
          <UFormField label="File (CSV or XLSX)" required>
            <UInput type="file" accept=".csv,.txt,.xlsx" @change="onFileChange" />
          </UFormField>
        </div>
        <div class="mt-4">
          <UButton type="submit" :loading="uploading" :disabled="!file"> Import </UButton>
        </div>
      </UForm>
    </UCard>

    <UCard>
      <template #header> Import History </template>
      <UTable :data="logs" :columns="[
        { accessorKey: 'id', header: '#', cell: ({ row }) => `#${row.getValue('id')}` },
        { accessorKey: 'entity_type', header: 'Entity' },
        { accessorKey: 'filename', header: 'File' },
        { accessorKey: 'total_rows', header: 'Total Rows' },
        { accessorKey: 'imported_rows', header: 'Imported' },
        { accessorKey: 'skipped_rows', header: 'Skipped' },
        { accessorKey: 'status', header: 'Status' },
      ]">
        <template #status-data="{ row }">
          <UBadge :color="row.original.status === 'completed' ? 'success' : row.original.status === 'partial' ? 'warning' : 'error'" variant="subtle">
            {{ row.original.status }}
          </UBadge>
        </template>
      </UTable>
    </UCard>
  </div>
</template>
