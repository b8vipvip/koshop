<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-[100] flex items-end justify-center bg-black/30 lg:items-center lg:p-6">
      <section class="flex h-[100dvh] w-full flex-col overflow-hidden bg-[#f3f5f7] text-[15px] text-slate-900 lg:h-[90vh] lg:max-w-[460px] lg:rounded-3xl">
        <header class="flex items-center gap-3 border-b border-slate-200 bg-white px-4 py-3">
          <button class="flex h-10 w-10 items-center justify-center text-4xl leading-none" @click="close">‹</button>
          <div class="min-w-0 flex-1">
            <b class="block text-[17px] leading-5">一家卡券充值店</b>
            <p class="mt-0.5 text-xs font-medium text-orange-500">真实体验分5.0 ★★★★★</p>
          </div>
          <button class="rounded-full border border-slate-300 px-4 py-1.5 text-[15px]" @click="goShop">店铺</button>
          <button class="flex h-10 w-10 items-center justify-center text-xl font-bold" @click="soon">•••</button>
        </header>

        <main ref="list" class="min-h-0 flex-1 space-y-4 overflow-y-auto p-4">
          <p class="text-center text-sm text-gray-400">{{ status }}</p>

          <div class="rounded-xl bg-white/80 p-3.5 text-[15px] leading-6 text-gray-700">
            欢迎您光临本店<br>
            欢迎光临，请问有什么可以帮您！
          </div>

          <div
            v-for="message in messages"
            :key="message.id"
            class="flex gap-2.5"
            :class="message.sender === 'buyer' ? 'flex-row-reverse' : ''"
          >
            <div
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-[15px] font-bold"
              :class="message.sender === 'buyer' ? 'bg-orange-100 text-orange-700' : 'bg-amber-100 text-amber-700'"
            >
              {{ message.sender === 'buyer' ? buyerInitial : '客' }}
            </div>

            <div class="max-w-[76%]">
              <p
                class="mb-1 text-sm text-gray-500"
                :class="message.sender === 'buyer' ? 'text-right' : ''"
              >
                {{ message.sender === 'buyer' ? buyerName : '店铺客服' }}
              </p>

              <div
                class="rounded-2xl px-3.5 py-2.5 text-[15px] leading-6 shadow-sm"
                :class="message.sender === 'buyer' ? 'bg-orange-500 text-white' : 'bg-white text-slate-900'"
              >
                <a v-if="message.type==='image'" :href="message.url" target="_blank">
                  <img :src="message.thumbnailUrl || message.url" class="max-h-60 max-w-full rounded-lg" alt="图片">
                </a>
                <video v-else-if="message.type==='video'" :src="message.url" :poster="message.thumbnailUrl" controls preload="metadata" class="max-w-full rounded-lg"></video>
                <a v-else-if="message.type==='file'" :href="message.url" target="_blank">📎 {{message.fileName||message.content}}</a>
                <template v-else>{{ message.content }}</template>
              </div>
              <p v-if="message.sender === 'buyer'" class="mt-1 text-right text-xs text-gray-400">{{ message.readText || (message.read ? '已读' : '未读') }}</p>
            </div>
          </div>
        </main>

        <footer class="border-t border-slate-300 bg-white px-3 py-2 pb-[max(10px,env(safe-area-inset-bottom))]">
          <div class="mb-2 flex gap-3 overflow-x-auto whitespace-nowrap text-sm">
            <button v-for="x in ['评价客服','猜你喜欢','店铺上新']" :key="x" class="rounded-md bg-white px-1 py-1" @click="soon">{{x}}</button>
          </div>

          <div v-if="emoji" class="mb-2 rounded-xl bg-white p-3 shadow-[0_-2px_12px_rgba(0,0,0,.06)]">
            <div class="grid grid-cols-8 gap-2">
              <button
                v-for="e in emojis"
                :key="e"
                type="button"
                class="flex h-10 items-center justify-center rounded-lg bg-slate-100 text-xl"
                @click="draft+=e"
              >
                {{e}}
              </button>
            </div>
          </div>

          <div v-if="more" class="mb-2 rounded-xl bg-white p-3 shadow-[0_-2px_12px_rgba(0,0,0,.06)]">
            <div class="grid grid-cols-4 gap-3 text-center">
              <button
                v-for="x in tools"
                :key="x.name"
                type="button"
                class="flex flex-col items-center gap-1 rounded-xl bg-slate-100 px-2 py-3 text-sm text-slate-700"
                @click="tool(x.name)"
              >
                <span class="text-2xl">{{ x.icon }}</span>
                <span>{{ x.name }}</span>
              </button>
            </div>
          </div>

          <form class="grid grid-cols-[46px_minmax(0,1fr)_46px_56px] items-center gap-2" @submit.prevent="send">
            <button
              type="button"
              class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-2xl"
              @click="soon"
            >
              🎙
            </button>

            <input
              v-model="draft"
              @focus="closePanels"
              @input="more=false"
              class="h-11 min-w-0 rounded-xl bg-gray-100 px-3 text-[15px] outline-none"
              placeholder="输入消息"
            >

            <button
              type="button"
              class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-2xl"
              @click="emoji=!emoji;more=false"
            >
              😊
            </button>

            <button
              v-if="draft.trim()"
              class="h-11 rounded-xl bg-orange-500 px-3 text-[15px] font-bold text-white"
            >
              发送
            </button>

            <button
              v-else
              type="button"
              class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-3xl leading-none"
              @click="more=!more;emoji=false"
            >
              ＋
            </button>
          </form>

          <input ref="camera" hidden type="file" accept="image/*" capture="environment" @change="upload($event,'image')">
          <input ref="image" hidden type="file" accept="image/jpeg,image/png,image/gif,image/webp" @change="upload($event,'image')">
          <input ref="video" hidden type="file" accept="video/mp4,video/webm,video/quicktime" @change="upload($event,'video')">
        </footer>
      </section>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useUserAuthStore } from '../stores/userAuth'
import { compressImage, getVideoThumbnail } from '../utils/chatMedia'

type Message = {
  id: number | string
  sender: 'buyer' | 'seller'
  type?: string
  content: string
  url?: string
  thumbnailUrl?: string
  fileName?: string
  read?: boolean
  readText?: string
}

const apiBase = import.meta.env.VITE_PUBLIC_CHAT_API_URL || 'https://kefu.cn12.vip/index.php/chn/koshopchat/public'
const auth = useUserAuthStore()
const router = useRouter()

const open = ref(false)
const more = ref(false)
const emoji = ref(false)
const draft = ref('')
const messages = ref<Message[]>([])
const status = ref('正在连接店铺客服…')
const list = ref<HTMLElement | null>(null)

const tools = [
  { name: '拍照', icon: '📷' },
  { name: '照片', icon: '🖼️' },
  { name: '语音', icon: '🎤' },
  { name: '商品', icon: '🛍️' },
  { name: '订单', icon: '📋' },
  { name: '打款', icon: '💴' },
  { name: '视频', icon: '🎬' },
  { name: '文件', icon: '📁' }
]

const emojis = ['😀','😃','😄','😁','😆','😂','😊','😍','😘','😎','😭','😡','👍','👎','🙏','🎉','❤️']

const camera = ref<HTMLInputElement>()
const image = ref<HTMLInputElement>()
const video = ref<HTMLInputElement>()
const session = ref<{ id: number; hash: string } | null>(null)
const timer = ref<number>()

const buyerName = computed(() => auth.isAuthenticated ? (auth.user?.nickname || auth.user?.email || `买家 ${auth.user?.id}`) : '访客')
const buyerInitial = computed(() => buyerName.value.slice(0, 1))

const closePanels = () => {
  emoji.value = false
  more.value = false
}

const request = async (action: string, options: RequestInit = {}) => {
  const s = session.value
  const url = new URL(`${apiBase}/${action}`)

  if (s) {
    url.searchParams.set('id', String(s.id))
    url.searchParams.set('hash', s.hash)
  }

  const r = await fetch(url, {
    ...options,
    headers: options.body instanceof FormData
      ? (options.headers || {})
      : { 'Content-Type': 'application/json', ...(options.headers || {}) }
  })

  const data = await r.json().catch(() => ({ message: '客服连接失败' }))

  if (!r.ok) {
    const e: any = new Error(data.message || '客服连接失败')
    e.status = r.status
    throw e
  }

  return data
}

const tool = (x: string) => {
  if (x === '拍照') {
    camera.value?.click()
  } else if (x === '照片') {
    image.value?.click()
  } else if (x === '视频') {
    video.value?.click()
  } else {
    soon()
  }
}

const upload = async (e: Event, type: string) => {
  const el = e.target as HTMLInputElement
  const f = el.files?.[0]
  if (!f || !session.value) return

  const max = type === 'image' ? 10 : 50
  if (f.size > max * 1024 * 1024) {
    status.value = `${type === 'image' ? '图片' : '视频'}不能超过 ${max}MB`
    return
  }

  status.value = '正在上传...'
  const uploadFile = type === 'image' ? await compressImage(f) : f
  const thumbnail = type === 'video' ? await getVideoThumbnail(f) : null
  const fd = new FormData()
  fd.append('file', uploadFile, f.name)
  if (thumbnail) fd.append('thumbnail', thumbnail, `${f.name}.thumbnail.jpg`)
  fd.append('type', type)

  try {
    const data = await request('upload', { method: 'POST', body: fd, headers: {} })
    messages.value.push(data.message)
    more.value = false
    await scroll()
  } catch (err: any) {
    status.value = err.message || '上传失败，请重试'
  } finally {
    el.value = ''
  }
}

const scroll = async () => {
  await nextTick()
  list.value?.scrollTo({ top: list.value.scrollHeight })
}

const load = async () => {
  if (!session.value) return

  try {
    const data = await request('messages')
    if (!data.ok) throw new Error(data.message || '消息同步失败')
    messages.value = data.items || []
    status.value = '店铺客服在线，您可以直接留言'
    await scroll()
  } catch (e: any) {
    if (e.status === 403 || String(e.message).includes('会话无效')) {
      localStorage.removeItem('koshop_public_chat')
      session.value = null
      await start()
      return
    }
    status.value = '消息同步失败，正在重试'
  }
}

const start = async () => {
  try {
    if (timer.value) clearInterval(timer.value)

    const saved = localStorage.getItem('koshop_public_chat')
    if (saved && !session.value) session.value = JSON.parse(saved)

    if (!session.value) {
      const data = await request('start', {
        method: 'POST',
        body: JSON.stringify({
          name: buyerName.value,
          avatar: auth.user?.avatar_url || '',
          userId: auth.user?.id || ''
        })
      })

      session.value = data.session
      localStorage.setItem('koshop_public_chat', JSON.stringify(data.session))
    }

    await load()

    if (open.value) {
      timer.value = window.setInterval(() => void load(), 2500)
    }
  } catch {
    status.value = '暂时无法连接客服，请稍后重试'
  }
}

const show = () => {
  open.value = true
  void start()
}

const close = () => {
  open.value = false
  more.value = false
  emoji.value = false
  if (timer.value) clearInterval(timer.value)
}

const send = async () => {
  const content = draft.value.trim()
  if (!content || !session.value) return

  try {
    const data = await request('send', {
      method: 'POST',
      body: JSON.stringify({ content })
    })

    if (!data.ok || !data.message) {
      throw new Error(data.message || '消息发送失败，请重试')
    }

    messages.value.push(data.message)
    draft.value = ''
    status.value = '店铺客服在线，您可以直接留言'
    await scroll()
  } catch (e: any) {
    status.value = e.message || '消息发送失败，请重试'
  }
}

const soon = () => alert('该功能即将上线')

const goShop = () => {
  close()
  void router.push('/')
}

onMounted(() => addEventListener('koshop:open-chat', show))
onBeforeUnmount(() => {
  removeEventListener('koshop:open-chat', show)
  close()
})
</script>
