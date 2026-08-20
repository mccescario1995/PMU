<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted, ref, computed } from 'vue'
import { usePermissions } from "~/composables/usePermissions";

definePageMeta({
  layout: "dashboard",
});

const { can } = usePermissions();

const UBadge = resolveComponent('UBadge')

const typeColor = {
  buyer: 'success' as const,
  broker: 'warning' as const,
  renter: 'neutral' as const,
}

const stakeholders = ref<any[]>([])

const searchQuery = ref('')

const filteredStakeholders = computed(() => {
  if (!searchQuery.value) return stakeholders.value
  const q = searchQuery.value.toLowerCase()
  return stakeholders.value.filter((s: any) => {
    const name = s.name?.toLowerCase() || ''
    const contact = s.contact_no?.toLowerCase() || ''
    const typeName = s.stakeholder_type?.name?.toLowerCase() || ''
    return name.includes(q) || contact.includes(q) || typeName.includes(q)
  })
})

onMounted(async () => {
  stakeholders.value = ((await apiFetch('/v1/stakeholders', { parseJson: true })) as any).data
})

type Stakeholder = {
  id: number;
  name: string;
  type: string;
  contact_no: string;
}

const columns: TableColumn<Stakeholder>[] = [
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
    accessorKey: 'type',
    header: 'Type',
    cell: ({ row }) => {
      const rawType = row.getValue('type')
      const typeName = row.original.stakeholder_type?.name ?? rawType
      const color = typeColor[rawType as keyof typeof typeColor]

      return h(UBadge, { class: 'capitalize', variant: 'subtle', color }, () =>
        typeName
      )
    }

  },
  {
    accessorKey: 'contact_no',
    header: 'Contact'
  },
  {
    accessorKey: 'action',
    header: 'Action'
  }
]
</script>

<template>
  <div class="p-6 space-y-5">
    <div class="flex justify-between items-center gap-4">
      <h1 class="text-2xl font-bold">Stakeholders</h1>

      <UInput v-model="searchQuery" placeholder="Search stakeholders..." icon="i-lucide-search" class="max-w-xs" />

      <UButton v-if="can('manage stakeholders')" to="/stakeholders/create" icon="i-lucide-plus">
        Add Stakeholder
      </UButton>
    </div>

    <UTable
      :data="filteredStakeholders"
      :columns="columns"
    >
      <template #action-cell="{ row }">
        <UButton size="xs" :to="`/stakeholders/${row.original.id}`" icon="i-lucide-eye" > </UButton>
      </template>
    </UTable>
  </div>
</template>