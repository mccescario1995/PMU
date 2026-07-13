<script setup lang="ts">
import LoginCard from '~/components/auth/LoginCard.vue';

</script>

<!-- pages/login.vue -->
<template>
  <div class="grid min-h-screen lg:grid-cols-[1.4fr_0.9fr]">
    <AuthHero />

    <div class="flex min-h-screen">
      <LoginCard />
    </div>
  </div>
</template>
