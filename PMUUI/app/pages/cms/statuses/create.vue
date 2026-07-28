<script setup lang="ts">
import { apiFetch } from '~/composables/useApiFetch'

definePageMeta({
  layout: "dashboard",
});

const form = reactive({
  name: '',
  type: 'inventory',
  color: 'green',
});

function save() {
  apiFetch('/v1/statuses', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(form),
    parseJson: true,
    throwOnError: true,
  }).then(() => useRouter().push('/cms/statuses'))
}
</script>

<template>
  <div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-5">Add Status</h1>

    <UForm :state="form" @submit="save">
      <UFormField label="Name">
        <UInput v-model="form.name" />
      </UFormField>

      <UFormField label="Type">
        <USelect v-model="form.type" :items="['inventory', 'transaction', 'stakeholder']" />
      </UFormField>

      <UFormField label="Color">
        <USelect v-model="form.color" :items="['green', 'yellow', 'red', 'blue', 'gray']" />
      </UFormField>

      <UButton type="submit"> Save </UButton>
    </UForm>
  </div>
</template>
