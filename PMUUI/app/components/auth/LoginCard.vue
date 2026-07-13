<script setup lang="ts">
import Copyright from "./Copyright.vue";
import LoginForm from "./LoginForm.vue";
</script>

<template>
  <UCard
    class="flex h-full w-full"
    :ui="{
      body: 'flex flex-col h-full p-0',
    }"
  >
    <AuthHeader />
    <div class="flex-1 flex items-center justify-center px-10">
      <LoginForm />
    </div>

    <Copyright />
  </UCard>
</template>
