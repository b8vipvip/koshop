<template>
  <div id="app" class="min-h-screen theme-page flex flex-col">
    <Navbar v-if="!hideTopNav" />
    <main class="flex-1 pb-14 lg:pb-0">
      <ErrorBoundary>
        <RouterView v-slot="{ Component }">
          <Transition name="page-fade" mode="out-in">
            <component :is="Component" />
          </Transition>
        </RouterView>
      </ErrorBoundary>
    </main>
    <Footer v-if="!hideTopNav" />
    <Loading :loading="appStore.loading" />
    <Toast />
    <ConfirmDialog />
    <BackToTop />
    <MobilePageBackButton />
    <MobileBottomNav v-if="!hideTopNav" />
    <CustomerChatOverlay />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAppStore } from './stores/app'
import Navbar from './components/Navbar.vue'
import Footer from './components/Footer.vue'
import Loading from './components/Loading.vue'
import Toast from './components/Toast.vue'
import ConfirmDialog from './components/ConfirmDialog.vue'
import ErrorBoundary from './components/ErrorBoundary.vue'
import BackToTop from './components/BackToTop.vue'
import MobileBottomNav from './components/MobileBottomNav.vue'
import MobilePageBackButton from './components/MobilePageBackButton.vue'
import CustomerChatOverlay from './components/CustomerChatOverlay.vue'

// config 由 router.beforeEach 统一加载，无需在此重复调用
const appStore = useAppStore()
const route = useRoute()
const topLevelPaths = new Set(['/', '/products', '/cart', '/me', '/auth/login', '/blog', '/notice', '/about'])
const hideTopNav = computed(() => route.meta.hideTopNav === true || !topLevelPaths.has(route.path))
</script>

<style>
.page-fade-enter-active,
.page-fade-leave-active {
  transition: opacity 200ms ease;
}

.page-fade-enter-from,
.page-fade-leave-to {
  opacity: 0;
}
</style>
