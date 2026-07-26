<script setup lang="ts">
import UserDropdown from '~/components/layouts/UserDropdown.vue'

const today = new Date()

const formatted = computed(() =>
  today.toLocaleDateString("en-PH", {
    weekday: "long",
    month: "long",
    day: "numeric",
    year: "numeric",
  }),
)
</script>

<template>
  <header
    class="flex h-20 items-center justify-between border-b bg-white px-8"
  >
    <div>

      <h1 class="text-3xl font-bold text-slate-800">
        Dashboard
      </h1>

      <p class="text-sm text-slate-500">
        {{ formatted }}
      </p>

    </div>

    <div class="flex items-center gap-4">

      <UButton
        icon="i-lucide-bell"
        color="neutral"
        variant="ghost"
      />

      <UserDropdown />

    </div>

  </header>
</template>