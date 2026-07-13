<template>
  <UDropdownMenu
    :items="[
      {
        label: 'Profile',
        icon: 'i-lucide-user',
      },
      {
        label: 'Settings',
        icon: 'i-lucide-settings',
      },
      {
        label: 'Logout',
        icon: 'i-lucide-log-out',
      },
    ]"
  >
    <UButton variant="ghost" color="neutral">
      <UAvatar />

      <div class="ml-3 text-left">
        <p class="font-semibold">Port Manager</p>

        <p class="text-xs text-gray-500">Administrator</p>
      </div>
    </UButton>
  </UDropdownMenu>
</template>
