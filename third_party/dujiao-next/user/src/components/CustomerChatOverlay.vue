<template>
  <Teleport to="body">
    <div v-if="open" class="koshop-chat-overlay fixed inset-0 z-[100] flex items-end justify-center bg-black/30 lg:items-center lg:p-6">
      <section :style="{ height: chatViewportHeight }" class="koshop-chat-panel flex w-full flex-col overflow-hidden bg-[#f3f5f7] text-[15px] text-slate-900 lg:max-h-[90vh] lg:max-w-[460px] lg:rounded-3xl">
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
                :class="message.sender === 'buyer' ? 'koshop-buyer-bubble bg-orange-500 text-white' : 'koshop-seller-bubble bg-white text-slate-900'"
              >
                <a v-if="message.type==='image'" :href="message.url" target="_blank">
                  <img :src="message.thumbnailUrl || message.url" class="max-h-60 max-w-full rounded-lg" alt="图片">
                </a>
                <video v-else-if="message.type==='video'" :src="message.url" :poster="message.thumbnailUrl" controls preload="metadata" class="max-w-full rounded-lg"></video>
                <a v-else-if="message.type==='file'" :href="message.url" target="_blank">📎 {{message.fileName||message.content}}</a>
                <div v-else-if="message.type==='product_card'" class="text-left"><img v-if="message.card?.cover" :src="message.card.cover" class="mb-2 max-h-32 rounded"><b>{{message.card?.title}}</b><p>¥{{message.card?.price}}</p><button class="underline" @click="openCard(message.card?.url)">查看商品</button></div><div v-else-if="message.type==='order_card'" class="text-left"><b>订单 {{message.card?.orderNo}}</b><p>{{message.card?.productTitle}}</p><p>¥{{message.card?.amount}} · {{message.card?.statusText}}</p><p>{{message.card?.createdAt}}</p><button class="underline" @click="openOrder(message.card)">查看订单</button></div><template v-else>{{ message.content }}</template>
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
                @click="appendEmoji(e)"
              >
                {{e}}
              </button>
            </div>
          </div>

          <div v-if="picker" class="mb-2 max-h-72 overflow-y-auto rounded-xl bg-white p-3 shadow"><b>{{picker==='products'?'选择商品':'选择订单'}}</b><button v-for="x in choices" :key="x.id" class="mt-2 block w-full rounded-lg bg-slate-100 p-3 text-left" @click="sendCard(x)"><b>{{picker==='products'?x.title:x.orderNo}}</b><p>{{picker==='products'?'¥'+x.price:(x.productTitle+' · ¥'+x.amount+' · '+x.statusText)}}</p></button></div>

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
              class="koshop-chat-icon-btn flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-2xl"
              @click="soon"
            >
              🎙
            </button>

            <input
              :value="draft"
              @focus="closePanels"
              @input="onDraftInput"
              @compositionstart="isComposing = true"
              @compositionend="onCompositionEnd"
              class="koshop-chat-input h-11 min-w-0 rounded-xl bg-gray-100 px-3 text-[15px] outline-none"
              placeholder="输入消息"
            >

            <button
              type="button"
              class="koshop-chat-icon-btn flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-2xl"
              @click="emoji=!emoji;more=false"
            >
              😊
            </button>

            <button
              v-if="hasDraft"
              type="submit"
              class="koshop-chat-send h-11 rounded-xl bg-orange-500 px-3 text-[15px] font-bold text-white"
            >
              发送
            </button>

            <button
              v-else
              type="button"
              class="koshop-chat-plus koshop-chat-icon-btn flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-3xl leading-none"
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
  card?: any
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
const isComposing = ref(false)
const chatViewportHeight = ref('100dvh')
const messages = ref<Message[]>([])
const status = ref('正在连接店铺客服…')
const list = ref<HTMLElement | null>(null)

const context = ref<any>({}); const choices=ref<any[]>([]); const picker=ref('')
const baseTools = [
  { name: '拍照', icon: '📷' },
  { name: '照片', icon: '🖼️' },
  { name: '语音', icon: '🎤' },
  { name: '商品', icon: '🛍️' },
  { name: '订单', icon: '📋' },
  { name: '打款', icon: '💴' },
  { name: '视频', icon: '🎬' },
  { name: '文件', icon: '📁' }
]

const tools=computed(()=>baseTools.filter(x=>x.name!=='商品'||context.value.product).filter(x=>x.name!=='订单'||context.value.hasOrders))
const emojis = ['😀','😃','😄','😁','😆','😂','😊','😍','😘','😎','😭','😡','👍','👎','🙏','🎉','❤️']

const camera = ref<HTMLInputElement>()
const image = ref<HTMLInputElement>()
const video = ref<HTMLInputElement>()
const session = ref<{ id: number; hash: string } | null>(null)
const timer = ref<number>()
let previousBodyOverflow = ''

const buyerName = computed(() => auth.isAuthenticated ? (auth.user?.nickname || auth.user?.email || `买家 ${auth.user?.id}`) : '访客')
const buyerInitial = computed(() => buyerName.value.slice(0, 1))
const hasDraft = computed(() => draft.value.trim().length > 0)

const updateChatViewportHeight = () => {
  chatViewportHeight.value = `${window.visualViewport?.height || window.innerHeight}px`
}

const addViewportListeners = () => {
  updateChatViewportHeight()
  window.addEventListener('resize', updateChatViewportHeight)
  window.addEventListener('orientationchange', updateChatViewportHeight)
  window.visualViewport?.addEventListener('resize', updateChatViewportHeight)
}

const removeViewportListeners = () => {
  window.removeEventListener('resize', updateChatViewportHeight)
  window.removeEventListener('orientationchange', updateChatViewportHeight)
  window.visualViewport?.removeEventListener('resize', updateChatViewportHeight)
}

const closePanels = () => {
  emoji.value = false
  more.value = false
}

const onDraftInput = (event: Event) => {
  draft.value = (event.target as HTMLInputElement).value
  if (hasDraft.value) more.value = false
}

const onCompositionEnd = (event: CompositionEvent) => {
  isComposing.value = false
  draft.value = (event.target as HTMLInputElement).value
  if (hasDraft.value) more.value = false
}

const appendEmoji = (value: string) => {
  draft.value += value
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

  try {
    const r = await fetch(url, {
      ...options,
      headers: options.body instanceof FormData
        ? (options.headers || {})
        : { 'Content-Type': 'application/json', ...(options.headers || {}) }
    })
    const data = await r.json().catch(() => ({ message: '客服接口返回异常' }))

    if (!r.ok || data.ok === false) {
      const error: any = new Error(data.message || '客服连接失败')
      error.status = r.status
      throw error
    }
    return data
  } catch (error) {
    console.error('[KoshopChat]', action, error)
    throw error
  }
}

const tool = (x: string) => {
  more.value = false
  emoji.value = false

  if (x === '拍照') {
    camera.value?.click()
  } else if (x === '照片') {
    image.value?.click()
  } else if (x === '视频') {
    video.value?.click()
  } else if (x === '商品' || x === '订单') { void openPicker(x === '商品' ? 'products' : 'orders') } else { soon() }
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
    emoji.value = false
    more.value = false
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
    if (saved && !session.value) {
      try {
        const parsed = JSON.parse(saved)
        if (!parsed || !Number.isFinite(Number(parsed.id)) || Number(parsed.id) <= 0 || typeof parsed.hash !== 'string' || !parsed.hash) {
          throw new Error('会话结构无效')
        }
        session.value = { id: Number(parsed.id), hash: parsed.hash }
      } catch (error) {
        console.error('[KoshopChat]', 'restore-session', error)
        localStorage.removeItem('koshop_public_chat')
        session.value = null
      }
    }

    if (!session.value) {
      const data = await request('start', {
        method: 'POST',
        body: JSON.stringify({
          name: buyerName.value,
          avatar: auth.user?.avatar_url || '',
          userId: auth.user?.id || '', browserId: browserId(), product: recentProduct(), guestOrderIds: guestOrderIds()
        })
      })

      if (!data.session?.id || !data.session?.hash) throw new Error('客服接口未返回有效会话')
      session.value = data.session
      localStorage.setItem('koshop_public_chat', JSON.stringify(data.session))
    }

    await loadContext()
    await load()

    if (open.value) {
      timer.value = window.setInterval(() => void load(), 2500)
    }
  } catch (error) {
    console.error('[KoshopChat]', 'start', error)
    status.value = '客服连接失败，请稍后重试'
  }
}

const show = () => {
  open.value = true
  previousBodyOverflow = document.body.style.overflow
  document.body.style.overflow = 'hidden'
  addViewportListeners()
  void start()
}

const close = () => {
  open.value = false
  more.value = false
  emoji.value = false
  if (timer.value) clearInterval(timer.value)
  removeViewportListeners()
  document.body.style.overflow = previousBodyOverflow
}

const send = async (canRebuildSession: boolean | Event = true) => {
  const content = draft.value.trim()
  if (!content || isComposing.value) return
  console.debug('[KoshopChat] send', content)

  try {
    if (!session.value) await start()
    if (!session.value) throw new Error('客服连接失败，请稍后重试')
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
    if (canRebuildSession !== false && (e.status === 403 || String(e.message).includes('会话无效'))) {
      localStorage.removeItem('koshop_public_chat')
      session.value = null
      await start()
      if (session.value) return send(false)
    }
    console.error('[KoshopChat]', 'send', e)
    status.value = '消息发送失败，请稍后重试'
  }
}


const browserId=()=>{let x=localStorage.getItem('koshop_browser_id');if(!x){x=crypto.randomUUID();localStorage.setItem('koshop_browser_id',x)}return x}
const recentProduct=()=>{try{return JSON.parse(localStorage.getItem('koshop_last_product')||'null')}catch{return null}}
const guestOrderIds=()=>{try{return JSON.parse(localStorage.getItem('koshop_guest_order_ids')||'[]')}catch{return []}}
async function loadContext(){try{context.value=await request('context')}catch{context.value={}}}
async function openPicker(kind:string){try{const d=await request(kind);choices.value=d.items||[];picker.value=kind}catch{picker.value=''}}
async function sendCard(x:any){const product=picker.value==='products';const payload=product?{koshopType:'product_card',productId:x.id,title:x.title,price:x.price,cover:x.cover,url:x.url,text:`[商品] ${x.title}`}:{koshopType:'order_card',orderId:x.id,orderNo:x.orderNo,productTitle:x.productTitle,amount:x.amount,statusText:x.statusText,createdAt:x.createdAt,text:`[订单] ${x.orderNo}`};const d=await request('send-card',{method:'POST',body:JSON.stringify(payload)});messages.value.push(d.message);picker.value='';await scroll()}
function openCard(url:string){if(url){close();location.href=url}}
function openOrder(card:any){close();void router.push(auth.isAuthenticated?`/me/orders/${card.id}`:`/guest/orders/${card.orderNo}`)}

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

<style scoped>
/* KOSHOP_CHAT_COLOR_CONTRAST_FIX_V1 */
.koshop-chat-overlay,
.koshop-chat-overlay * {
  color-scheme: light !important;
  forced-color-adjust: none;
  -webkit-tap-highlight-color: transparent;
}

.koshop-chat-panel {
  background: #f3f5f7 !important;
  color: #111827 !important;
  -webkit-text-fill-color: #111827;
}

.koshop-chat-panel header,
.koshop-chat-panel footer {
  background: #ffffff !important;
  color: #111827 !important;
  -webkit-text-fill-color: #111827;
}

.koshop-buyer-bubble {
  background: #ff6a00 !important;
  color: #ffffff !important;
  -webkit-text-fill-color: #ffffff !important;
  opacity: 1 !important;
}

.koshop-buyer-bubble *:not(img):not(video) {
  color: #ffffff !important;
  -webkit-text-fill-color: #ffffff !important;
}

.koshop-seller-bubble {
  background: #ffffff !important;
  color: #111827 !important;
  -webkit-text-fill-color: #111827 !important;
  opacity: 1 !important;
}

.koshop-seller-bubble *:not(img):not(video) {
  color: #111827 !important;
  -webkit-text-fill-color: #111827 !important;
}

.koshop-chat-input {
  background: #f1f2f6 !important;
  color: #111827 !important;
  -webkit-text-fill-color: #111827 !important;
  caret-color: #ff6a00 !important;
  opacity: 1 !important;
  border: 0 !important;
  outline: none !important;
  appearance: none;
  -webkit-appearance: none;
}

.koshop-chat-input::placeholder {
  color: #8b95a1 !important;
  -webkit-text-fill-color: #8b95a1 !important;
  opacity: 1 !important;
}

.koshop-chat-send {
  background: #ff6a00 !important;
  color: #ffffff !important;
  -webkit-text-fill-color: #ffffff !important;
  opacity: 1 !important;
  visibility: visible !important;
  border: 0 !important;
  appearance: none;
  -webkit-appearance: none;
}

.koshop-chat-icon-btn,
.koshop-chat-plus {
  background: #f1f5f9 !important;
  color: #111827 !important;
  -webkit-text-fill-color: #111827 !important;
  opacity: 1 !important;
  border: 0 !important;
  appearance: none;
  -webkit-appearance: none;
}

@media (prefers-color-scheme: dark) {
  .koshop-chat-panel {
    background: #f3f5f7 !important;
    color: #111827 !important;
    -webkit-text-fill-color: #111827;
  }

  .koshop-chat-panel header,
  .koshop-chat-panel footer {
    background: #ffffff !important;
    color: #111827 !important;
    -webkit-text-fill-color: #111827;
  }

  .koshop-chat-input {
    background: #f1f2f6 !important;
    color: #111827 !important;
    -webkit-text-fill-color: #111827 !important;
  }

  .koshop-chat-send {
    background: #ff6a00 !important;
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
  }

  .koshop-buyer-bubble {
    background: #ff6a00 !important;
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
  }

  .koshop-seller-bubble {
    background: #ffffff !important;
    color: #111827 !important;
    -webkit-text-fill-color: #111827 !important;
  }
}
</style>
