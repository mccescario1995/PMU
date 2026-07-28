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

const UBadge = resolveComponent('UBadge')

const accounts = ref<any[]>([])
const roles = ref<any[]>([])
const loading = ref(true)
const showForm = ref(false)
const editing = ref<any>(null)

const form = reactive({
  name: '',
  email: '',
  password: '',
  status: 'active',
  roles: [] as string[],
})

onMounted(async () => {
  loading.value = true
  accounts.value = ((await apiFetch('/v1/users', { parseJson: true })) as any).data
  roles.value = ((await apiFetch('/v1/roles', { parseJson: true })) as any).data
  loading.value = false
})

function openCreate() {
  editing.value = null
  form.name = ''
  form.email = ''
  form.password = ''
  form.status = 'active'
  form.roles = []
  showForm.value = true
}

function openEdit(row: any) {
  editing.value = row
  form.name = row.name
  form.email = row.email
  form.password = ''
  form.status = row.status ?? 'active'
  form.roles = row.roles?.map((r: any) => r.name) ?? []
  showForm.value = true
}

async function save() {
  const payload: any = {
    name: form.name,
    email: form.email,
    status: form.status,
  }

  if (form.password) {
    payload.password = form.password
  }

  if (editing.value) {
    await apiFetch(`/v1/users/${editing.value.id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
      parseJson: true,
    })
  } else {
    payload.password = form.password
    await apiFetch('/v1/users', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
      parseJson: true,
    })
  }
  showForm.value = false
  accounts.value = ((await apiFetch('/v1/users', { parseJson: true })) as any).data
}

async function remove(row: any) {
  if (!confirm('Delete this account?')) return
  await apiFetch(`/v1/users/${row.id}`, { method: 'DELETE' })
  accounts.value = accounts.value.filter((a: any) => a.id !== row.id)
}

const statusColor: Record<string, string> = {
  active: 'success',
  inactive: 'neutral',
  suspended: 'error',
}

const roleOptions = roles.value.map((r: any) => r.name)

type Account = {
  id: number
  name: string
  email: string
  status: string
  roles: string[]
}

const columns: TableColumn<Account>[] = [
  { accessorKey: 'id', header: '#' },
  { accessorKey: 'name', header: 'Name' },
  { accessorKey: 'email', header: 'Email' },
  {
    accessorKey: 'status',
    header: 'Status',
    cell: ({ row }) => {
      const s = row.getValue('status')
      return h(UBadge, { color: statusColor[s as string] ?? 'neutral', variant: 'subtle' }, () => s)
    },
  },
  {
    accessorKey: 'roles',
    header: 'Roles',
    cell: ({ row }) => {
      const r = row.getValue('roles')
      return h('span', { class: 'text-xs' }, () => (r ?? []).join(', ') || '-')
    },
  },
  { accessorKey: 'action', header: 'Action' },
]
</script>

<template>
  <div class="p-6 space-y-5">
    <div class="flex justify-between">
      <h1 class="text-2xl font-bold">Accounts</h1>
      <UButton v-if="can('manage users')" icon="i-lucide-plus" @click="openCreate"> Add User </UButton>
    </div>

    <UTable :data="accounts" :columns="columns" :loading="loading">
      <template #action-cell="{ row }">
        <UButton v-if="can('manage users')" size="xs" @click="openEdit(row.original)"> Edit </UButton>
        <UButton v-if="can('manage users')" size="xs" color="error" variant="ghost" @click="remove(row.original)"> Delete </UButton>
      </template>
    </UTable>

    <UModal v-model:open="showForm">
      <UCard>
        <template #header> {{ editing ? 'Edit Account' : 'New Account' }} </template>
        <div class="space-y-4">
          <UFormField label="Name">
            <UInput v-model="form.name" />
          </UFormField>
          <UFormField label="Email">
            <UInput v-model="form.email" :disabled="!!editing" />
          </UFormField>
          <UFormField label="Password">
            <UInput v-model="form.password" type="password" :placeholder="editing ? 'Leave blank to keep current' : 'Enter password'" />
          </UFormField>
          <UFormField label="Status">
            <USelect v-model="form.status" :items="['active', 'inactive', 'suspended']" />
          </UFormField>
          <UFormField label="Roles">
            <USelect v-model="form.roles" :items="roleOptions" multiple />
          </UFormField>
        </div>
        <template #footer>
          <div class="flex justify-end gap-2">
            <UButton variant="ghost" @click="showForm = false">Cancel</UButton>
            <UButton @click="save">Save</UButton>
          </div>
        </template>
      </UCard>
    </UModal>
  </div>
</template>
