<template>
  <div class="text-center text-gray-400 text-sm">
    <div class="p-10">
      <div
        class="flex items-center gap-4 rounded-xl bg-slate-800 p-6 text-white"
      >
        <UIcon name="i-lucide-info" class="h-8 w-8 text-amber-400" />

        <p class="text-base leading-relaxed">
          You are currently not logged in. You need to be authenticated before
          accessing the Fish Port Operations Management System.
        </p>
      </div>
    </div>
    © {{ new Date().getFullYear() }}
    
    Port Management Unit Pasacao
  </div>
</template>
