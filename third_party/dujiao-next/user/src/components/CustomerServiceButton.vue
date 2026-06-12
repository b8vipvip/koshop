<template>
  <a
    :href="customerServiceUrl"
    target="_blank"
    rel="noopener noreferrer"
    class="inline-flex items-center justify-center gap-2 rounded-xl border theme-btn-secondary shadow-sm transition hover:-translate-y-0.5"
    :class="compact ? 'h-12 w-12 shrink-0 p-0' : 'min-h-[44px] px-4 py-2 text-sm font-bold'"
    aria-label="联系客服"
    title="联系客服"
  >
    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 10a6 6 0 10-12 0v4a2 2 0 002 2h1v-5H6m12 0h-3v5h1a2 2 0 002-2v-4m-1 8c-.8 1.2-1.8 2-3 2" />
    </svg>
    <span v-if="!compact">联系客服</span>
  </a>
</template>

<script setup lang="ts">
withDefaults(defineProps<{ compact?: boolean }>(), { compact: false })

const customerServiceUrl = import.meta.env.VITE_CUSTOMER_SERVICE_URL || 'https://kefu.cn12.vip/index.php/chat/start'
</script>
