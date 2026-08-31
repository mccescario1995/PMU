<script setup lang="ts">
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

definePageMeta({
  layout: "dashboard",
});

const route = useRoute()
const id = route.params.id

const log = ref<any>(null)
const loading = ref(true)

onMounted(async () => {
  loading.value = true
  log.value = (await apiFetch(`/v1/audit-logs/${id}`, { parseJson: true })) as any
  loading.value = false
})

const actionColor: Record<string, string> = {
  create: 'success',
  update: 'info',
  delete: 'error',
  login: 'primary',
  logout: 'neutral',
}

function formatJson(value: any): string {
  if (!value) return '-'
  return JSON.stringify(value, null, 2)
}

function formatDate(date: string): string {
  if (!date) return '-'
  return new Date(date).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: true,
  })
}

function diffFields(oldVals: any, newVals: any): string[] {
  if (!oldVals || !newVals) return []
  return Object.keys(newVals).filter((key) => {
    const oldVal = oldVals[key]
    const newVal = newVals[key]
    return JSON.stringify(oldVal) !== JSON.stringify(newVal)
  })
}
</script>

<template>
  <div class="p-6 space-y-5">
    <div class="flex items-center gap-4">
      <UButton variant="ghost" icon="i-lucide-arrow-left" to="/cms/audit-logs" />
      <h1 class="text-2xl font-bold">Audit Log Details</h1>
    </div>

    <div v-if="loading" class="flex justify-center py-10">
      <UButton loading variant="ghost" />
    </div>

    <div v-else-if="!log" class="text-center text-slate-500 py-10">
      Audit log not found.
    </div>

    <div v-else class="space-y-5">
      <UCard>
        <template #header>
          <div class="flex items-center gap-3">
            <UBadge :color="actionColor[log.action] ?? 'neutral'" variant="subtle" class="capitalize">
              {{ log.action }}
            </UBadge>
            <span class="font-semibold">{{ log.table_name }}</span>
            <span class="text-slate-500">Record #{{ log.record_id }}</span>
          </div>
        </template>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <p class="text-sm text-slate-500">User</p>
            <p class="font-medium">{{ log.user?.name ?? 'System' }}</p>
          </div>
          <div>
            <p class="text-sm text-slate-500">Timestamp</p>
            <p class="font-medium">{{ formatDate(log.created_at) }}</p>
          </div>
        </div>
      </UCard>

      <div v-if="log.old_values || log.new_values" class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <UCard v-if="log.old_values">
          <template #header>
            <h2 class="font-semibold text-error">Old Values</h2>
          </template>
          <pre class="bg-slate-50 dark:bg-slate-800 rounded-lg p-4 overflow-x-auto text-sm font-mono whitespace-pre-wrap">{{ formatJson(log.old_values) }}</pre>
        </UCard>

        <UCard v-if="log.new_values">
          <template #header>
            <h2 class="font-semibold text-success">New Values</h2>
          </template>
          <pre class="bg-slate-50 dark:bg-slate-800 rounded-lg p-4 overflow-x-auto text-sm font-mono whitespace-pre-wrap">{{ formatJson(log.new_values) }}</pre>
        </UCard>
      </div>

      <div v-if="log.old_values && log.new_values">
        <UCard>
          <template #header>
            <h2 class="font-semibold">Changed Fields</h2>
          </template>
          <div v-if="diffFields(log.old_values, log.new_values).length === 0" class="text-slate-500">
            No fields changed.
          </div>
          <div v-else class="space-y-3">
            <div v-for="field in diffFields(log.old_values, log.new_values)" :key="field" class="border-b last:border-0 pb-3 last:pb-0">
              <p class="font-medium text-sm mb-1">{{ field }}</p>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="bg-slate-50 dark:bg-slate-800 rounded p-3">
                  <p class="text-xs text-slate-500 mb-1">Old</p>
                  <p class="text-sm font-mono">{{ log.old_values[field] ?? 'null' }}</p>
                </div>
                <div class="bg-slate-50 dark:bg-slate-800 rounded p-3">
                  <p class="text-xs text-slate-500 mb-1">New</p>
                  <p class="text-sm font-mono">{{ log.new_values[field] ?? 'null' }}</p>
                </div>
              </div>
            </div>
          </div>
        </UCard>
      </div>
    </div>
  </div>
</template>
