<script setup lang="ts">
defineProps<{
  role: string;
}>();
</script>

<template>
  <UBadge size="lg" color="primary" variant="soft">
    {{ role }}
  </UBadge>
</template>
