<script setup lang="ts">
import { apiFetch } from '~/composables/useApiFetch'

definePageMeta({
  layout: "dashboard",
});

const form = reactive({
  key: '',
  value: '',
  type: 'string',
  description: '',
});

function save() {
  apiFetch('/v1/settings', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(form),
    parseJson: true,
    throwOnError: true,
  }).then(() => useRouter().push('/cms/settings'))
}
</script>

<template>
  <div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-5">Add Setting</h1>

    <UForm :state="form" @submit="save">
      <UFormField label="Key">
        <UInput v-model="form.key" />
      </UFormField>

      <UFormField label="Value">
        <UInput v-model="form.value" />
      </UFormField>

      <UFormField label="Type">
        <USelect v-model="form.type" :items="['string', 'number', 'boolean', 'json']" />
      </UFormField>

      <UFormField label="Description">
        <UTextarea v-model="form.description" />
      </UFormField>

      <UButton type="submit"> Save </UButton>
    </UForm>
  </div>
</template>
