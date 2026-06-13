<template>
  <button
    type="button"
    class="inline-flex items-center justify-center gap-2 rounded-xl border theme-btn-secondary shadow-sm transition hover:-translate-y-0.5"
    :class="compact ? 'h-12 w-12 shrink-0 p-0' : 'min-h-[44px] px-4 py-2 text-sm font-bold'"
    aria-label="联系客服"
    title="联系客服"
    @click="openCustomerService"
  >
    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 10a6 6 0 10-12 0v4a2 2 0 002 2h1v-5H6m12 0h-3v5h1a2 2 0 002-2v-4m-1 8c-.8 1.2-1.8 2-3 2" /></svg>
    <span v-if="!compact">联系客服</span>
  </button>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useUserAuthStore } from '../stores/userAuth'

withDefaults(defineProps<{ compact?: boolean }>(), { compact: false })

const auth = useUserAuthStore()
const baseUrl = import.meta.env.VITE_CUSTOMER_SERVICE_URL || 'https://kefu.cn12.vip/index.php/chn/chat/start'
const defaultAvatar = `${window.location.origin}/dj.svg`

const customerServiceUrl = computed(() => {
  const user = auth.user
  const name = auth.isAuthenticated && user
    ? user.nickname?.trim() || user.email || `买家 ${user.id}`
    : '访客'
  const avatar = user?.avatar_url || defaultAvatar
  const params = [
    ['nick', name],
    ['email', user?.email || ''],
    ['identifier', auth.isAuthenticated && user ? `koshop-buyer-${user.id}` : 'koshop-guest'],
    ['avatar', avatar],
  ]
  return params.reduce((url, [key, value]) => `${url}/(${key})/${encodeURIComponent(value)}`, baseUrl.replace(/\/$/, ''))
})

const openCustomerService = () => {
  const widget = window as Window & {
    lh_inst?: { startChat?: () => void }
    LHCChatOptions?: { open?: () => void }
  }
  if (typeof widget.lh_inst?.startChat === 'function') {
    widget.lh_inst.startChat()
    return
  }
  if (typeof widget.LHCChatOptions?.open === 'function') {
    widget.LHCChatOptions.open()
    return
  }
  window.open(customerServiceUrl.value, '_blank', 'noopener,noreferrer')
}
</script>
