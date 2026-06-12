<template>
  <button
    v-if="showBackButton"
    type="button"
    class="lg:hidden fixed left-3 top-[4.5rem] z-30 inline-flex h-10 w-10 items-center justify-center rounded-full border theme-panel-strong theme-border shadow-lg backdrop-blur-xl"
    aria-label="返回上一页"
    @click="goBack"
  >
    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 19l-7-7 7-7" />
    </svg>
  </button>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()
const topLevelPaths = new Set(['/', '/products', '/cart', '/auth/login', '/me'])
const showBackButton = computed(() => !topLevelPaths.has(route.path))

const goBack = () => {
  if (window.history.length > 1) router.back()
  else void router.push('/')
}
</script>
