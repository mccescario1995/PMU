<script setup lang="ts">
import ResetPasswordCard from '~/components/auth/ResetPasswordCard.vue';
</script>

<template>
  <div class="bg-white min-h-screen">
    <div class="grid min-h-screen lg:grid-cols-[1.4fr_0.9fr]">
      <AuthHero />

      <div class="flex min-h-screen">
        <ResetPasswordCard />
      </div>
    </div>
  </div>
</template>