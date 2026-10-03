<script setup lang="ts">
import ForgotPasswordCard from '~/components/auth/ForgotPasswordCard.vue';
</script>

<template>
  <div class="bg-white min-h-screen">
    <div class="grid min-h-screen lg:grid-cols-[1.4fr_0.9fr]">
      <AuthHero />

      <div class="flex min-h-screen">
        <ForgotPasswordCard />
      </div>
    </div>
  </div>
</template>