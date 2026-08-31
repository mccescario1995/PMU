<script setup lang="ts">
import ThemeToggle from '~/components/layouts/ThemeToggle.vue'
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
    class="flex h-20 items-center justify-between border-b bg-white px-8 dark:border-slate-700 dark:bg-slate-800"
  >
    <div>

      <h1 class="text-3xl font-bold text-slate-800 dark:text-slate-100">
        Dashboard
      </h1>

      <p class="text-sm text-slate-500 dark:text-slate-400">
        {{ formatted }}
      </p>

    </div>

    <div class="flex items-center gap-4">

      <ThemeToggle />

      <UButton
        icon="i-lucide-bell"
        color="neutral"
        variant="ghost"
      />

      <UserDropdown />

    </div>

  </header>
</template>