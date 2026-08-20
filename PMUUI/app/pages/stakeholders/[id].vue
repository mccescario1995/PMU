<script setup lang="ts">
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted } from 'vue'

definePageMeta({
  layout: "dashboard",
});

const route = useRoute();
const id = route.params.id;

const stakeholder = ref<any>({ id: Number(id), name: "Loading...", type: "-", contact_no: "-", email: "-", address: "-" })

onMounted(async () => {
  stakeholder.value = ((await apiFetch('/v1/stakeholders/' + id, { parseJson: true })) as any).data
});
</script>

<template>
  <div class="p-6 max-w-2xl space-y-5">
    <UButton icon="i-lucide-arrow-left" variant="ghost" to="/stakeholders"> Back </UButton>

    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold">{{ stakeholder.name }}</h1>
        <p class="text-slate-500">Stakeholder #{{ stakeholder.id }}</p>
      </div>
      <UButton :to="`/stakeholders/edit/${stakeholder.id}`" icon="i-lucide-pencil" > Edit </UButton>
    </div>

    <UCard>
      <template #header> Details </template>
      <dl class="divide-y divide-slate-100">
        <div class="flex justify-between py-2">
          <dt class="text-slate-500">Type</dt>
          <dd class="font-medium">{{ stakeholder.stakeholder_type?.name ?? stakeholder.type }}</dd>
        </div>
        <div class="flex justify-between py-2">
          <dt class="text-slate-500">Contact</dt>
          <dd class="font-medium">{{ stakeholder.contact_no }}</dd>
        </div>
        <div class="flex justify-between py-2">
          <dt class="text-slate-500">Email</dt>
          <dd class="font-medium">{{ stakeholder.email }}</dd>
        </div>
        <div class="flex justify-between py-2">
          <dt class="text-slate-500">Address</dt>
          <dd class="font-medium">{{ stakeholder.address }}</dd>
        </div>
      </dl>
    </UCard>
  </div>
</template>
