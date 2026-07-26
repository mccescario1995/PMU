<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted } from 'vue'
import { usePermissions } from "~/composables/usePermissions";

definePageMeta({
  layout: "dashboard",
});

const { can } = usePermissions();

const UBadge = resolveComponent('UBadge')

const statusColor = {
  active: 'success' as const,
  inactive: 'neutral' as const,
  suspended: 'error' as const,
}

const accounts = ref<any[]>([])

const loading = ref(true)

onMounted(async () => {
  loading.value = true
  accounts.value = ((await apiFetch('/v1/users', { parseJson: true })) as any).data
  loading.value = false
})

async function remove(row: any) {
  if (!confirm('Delete this account?')) return
  await apiFetch(`/v1/users/${row.id}`, { method: 'DELETE' })
  accounts.value = accounts.value.filter((a: any) => a.id !== row.id)
}

type Account = {
  id: number;
  name: string;
  email: string;
  status: keyof typeof statusColor;
  roles: string[];
}

const columns: TableColumn<Account>[] = [
  {
    accessorKey: 'id',
    header: '#',
    cell: ({ row }) => `#${row.getValue('id')}`
  },
  {
    accessorKey: 'name',
    header: 'Name'
  },
  {
    accessorKey: 'email',
    header: 'Email'
  },
  {
    accessorKey: 'roles',
    header: 'Roles',
    cell: ({ row }) => (row.getValue('roles') as string[])?.join(', ') ?? '-'
  },
  {
    accessorKey: 'status',
    header: 'Status',
    cell: ({ row }) => {
      const status = row.getValue('status') as keyof typeof statusColor
      const color = statusColor[status] ?? 'neutral'

      return h(UBadge, { class: 'capitalize', variant: 'subtle', color }, () =>
        status
      )
    }
  },
  {
    accessorKey: 'action',
    header: 'Action'
  }
]
</script>

<template>
  <div class="p-6 space-y-5">
    <div class="flex justify-between">
      <h1 class="text-2xl font-bold">Accounts</h1>

      <UButton v-if="can('manage users')" to="/accounts/create" icon="i-lucide-plus">
        Add Account
      </UButton>
    </div>

    <UTable
      :data="accounts"
      :columns="columns"
      :loading="loading"
    >
      <template #action-data="{ row }">
        <UButton size="xs" :to="`/accounts/edit/${row.id}`"> View </UButton>
        <UButton v-if="can('manage users')" size="xs" :to="`/accounts/edit/${row.id}`"> Edit </UButton>
        <UButton v-if="can('manage users')" size="xs" color="error" variant="ghost" @click="remove(row)"> Delete </UButton>
      </template>
    </UTable>
  </div>
</template>
