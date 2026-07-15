<template>
  <UCard>
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-gray-500">{{ label }}</p>
        <h2 class="text-3xl font-bold mt-1">{{ value }}</h2>
      </div>
      <div class="size-12 flex items-center justify-center rounded-full bg-gray-50">
        <UIcon :name="icon" class="size-6" :class="color" />
      </div>
    </div>
  </UCard>
</template>

<script setup lang="ts">
defineProps({
  icon: { type: String, required: true },
  label: { type: String, required: true },
  value: { type: String, required: true },
  color: { type: String, default: "text-slate-700" },
});
</script>
