import { onUnmounted, ref, watch } from 'vue'
import { useRoute } from '#imports'

const refreshCallbacks = ref<(() => void)[]>([])
let isWatcherActive = false

function registerRefresh(callback: () => void) {
  refreshCallbacks.value.push(callback)
  
  if (!isWatcherActive) {
    const route = useRoute()
    watch(
      () => route.path,
      () => {
        refreshCallbacks.value.forEach(cb => cb())
      },
      { immediate: false }
    )
    isWatcherActive = true
  }
}

function unregisterRefresh(callback: () => void) {
  const idx = refreshCallbacks.value.indexOf(callback)
  if (idx > -1) {
    refreshCallbacks.value.splice(idx, 1)
  }
}

export function useRouteRefresh() {
  const callbacks = ref<(() => void)[]>([])

  const register = (callback: () => void) => {
    callbacks.value.push(callback)
    registerRefresh(callback)
  }

  const unregister = (callback: () => void) => {
    const idx = callbacks.value.indexOf(callback)
    if (idx > -1) {
      callbacks.value.splice(idx, 1)
    }
    unregisterRefresh(callback)
  }

  onUnmounted(() => {
    callbacks.value.forEach(unregisterRefresh)
  })

  return { registerRefresh: register, unregisterRefresh: unregister }
}