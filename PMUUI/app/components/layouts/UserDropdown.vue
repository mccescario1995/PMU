<script setup lang="ts">
const { user, logout } = useAuth()
const router = useRouter()

const dropdownItems = [
  [
    {
      label: user.value?.name || 'User Profile',
      icon: 'i-lucide-user',
      to: '/profile',
    },
    {
      label: 'Settings',
      icon: 'i-lucide-settings',
      to: '/settings',
    },
    {
      label: 'Change Password',
      icon: 'i-lucide-lock',
      to: '/profile/change_password',
    },
  ],
  [
    {
      label: 'Logout',
      icon: 'i-lucide-log-out',
      onSelect: async () => {
        await logout()
      },
    },
  ],
]
</script>

<template>
  <UDropdownMenu
    :items="dropdownItems"
  >
    <UButton variant="ghost" color="neutral">
      <UAvatar
        :src="user?.profile_picture || undefined"
        :name="user?.name || 'User'"
        size="md"
      />

      <div class="ml-3 text-left">
        <p class="font-semibold">
          {{ user?.name || 'Loading...' }}
        </p>

        <p class="text-xs text-gray-500 dark:text-slate-400">
          {{ user?.email || '' }}
        </p>
      </div>
    </UButton>
  </UDropdownMenu>
</template>