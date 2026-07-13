<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted } from 'vue'

definePageMeta({
  layout: "dashboard",
});

const UBadge = resolveComponent('UBadge')

const statusColor = {
  active: 'success' as const,
  inactive: 'neutral' as const,
  suspended: 'error' as const,
}

const accounts = ref<any[]>([])

onMounted(async () => {
  accounts.value = (await apiFetch('/v1/users', { parseJson: true })) as any[]
})

type Account = {
  id: number;
  name: string;
  email: string;
  role: { id: number; name: string } | null;
  status: keyof typeof statusColor;
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
    accessorKey: 'role',
    header: 'Role',
    cell: ({ row }) => row.getValue('role')?.name ?? '-'
  },
  {
    accessorKey: 'status',
    header: 'Status',
    cell: ({ row }) => {
      const status = row.getValue('status') as keyof typeof statusColor
      const color = statusColor[status]

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

      <UButton to="/accounts/create" icon="i-lucide-plus">
        Add Account
      </UButton>
    </div>

    <UTable
      :data="accounts"
      :columns="columns"
    >
      <template #action-data="{ row }">
        <UButton size="xs" :to="`/accounts/edit/${row.id}`"> Edit </UButton>
      </template>
    </UTable>
  </div>
</template>
