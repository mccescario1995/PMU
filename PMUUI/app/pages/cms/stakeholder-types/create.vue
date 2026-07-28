<script setup lang="ts">
import { apiFetch } from '~/composables/useApiFetch'

definePageMeta({
  layout: "dashboard",
});

const form = reactive({
  name: "",
  description: "",
});

function save() {
  apiFetch('/v1/stakeholder-types', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(form),
    parseJson: true,
    throwOnError: true,
  }).then(() => useRouter().push('/cms/stakeholder-types'))
}
</script>

<template>
  <div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-5">Add Stakeholder Type</h1>

    <UForm :state="form" @submit="save">
      <UFormField label="Name">
        <UInput v-model="form.name" />
      </UFormField>

      <UFormField label="Description">
        <UTextarea v-model="form.description" />
      </UFormField>

      <UButton type="submit"> Save </UButton>
    </UForm>
  </div>
</template>
