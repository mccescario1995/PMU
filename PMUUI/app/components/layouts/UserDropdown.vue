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
        :name="user?.name || 'User'"
        :src="undefined"
      />

      <div class="ml-3 text-left">
        <p class="font-semibold">
          {{ user?.name || 'Loading...' }}
        </p>

        <p class="text-xs text-gray-500">
          {{ user?.email || '' }}
        </p>
      </div>
    </UButton>
  </UDropdownMenu>
</template>