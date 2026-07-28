<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted, h } from 'vue'
import { ref } from 'vue'
import { usePermissions } from "~/composables/usePermissions";

definePageMeta({
  layout: "dashboard",
});

const { can } = usePermissions();

const logs = ref<any[]>([])
const loading = ref(true)

onMounted(async () => {
  loading.value = true
  logs.value = ((await apiFetch('/v1/audit-logs', { parseJson: true })) as any).data
  loading.value = false
})

const actionColor: Record<string, string> = {
  create: 'success',
  update: 'info',
  delete: 'error',
  login: 'primary',
  logout: 'neutral',
}

type AuditLog = {
  id: number
  user_name: string
  action: string
  model_type: string
  model_id: number
  created_at: string
}

const columns: TableColumn<AuditLog>[] = [
  { accessorKey: 'id', header: '#' },
  {
    accessorKey: 'action',
    header: 'Action',
    cell: ({ row }) => {
      const a = row.getValue('action')
      return h('span', { class: 'capitalize' }, () => a)
    },
  },
  { accessorKey: 'user_name', header: 'User' },
  { accessorKey: 'model_type', header: 'Model' },
  { accessorKey: 'model_id', header: 'Model ID' },
  {
    accessorKey: 'created_at',
    header: 'Timestamp',
    cell: ({ row }) => new Date(row.getValue('created_at')).toLocaleString('en-US'),
  },
]
</script>

<template>
  <div class="p-6 space-y-5">
    <div class="flex justify-between">
      <h1 class="text-2xl font-bold">Audit Logs</h1>
      <UButton v-if="can('manage settings')" icon="i-lucide-download" @click="alert('Export coming soon')"> Export </UButton>
    </div>

    <UTable :data="logs" :columns="columns" :loading="loading">
      <template #action-cell="{ row }">
        <UButton size="xs" variant="ghost" @click="alert('Audit details coming soon')"> Details </UButton>
      </template>
    </UTable>
  </div>
</template>
