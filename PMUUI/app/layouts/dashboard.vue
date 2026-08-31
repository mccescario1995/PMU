<script setup lang="ts">
import AppBreadcrumb from '~/components/layouts/AppBreadcrumb.vue';
import AppFooter from '~/components/layouts/AppFooter.vue';
import AppHeader from '~/components/layouts/AppHeader.vue';
import AppSidebar from '~/components/layouts/AppSidebar.vue';

</script>

<template>
  <div class="flex h-screen overflow-hidden bg-slate-100 dark:bg-slate-900">

    <AppSidebar />

    <div class="flex flex-1 flex-col overflow-hidden">

      <AppHeader />

      <main class="flex-1 overflow-y-auto">

        <div class="p-8">

          <AppBreadcrumb />

          <div class="mt-6">
            <slot />
          </div>

        </div>

      </main>

      <AppFooter />

    </div>

  </div>
</template>