<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-[100] flex items-end justify-center bg-black/30 lg:items-center lg:p-6">
      <section class="flex h-[100dvh] w-full flex-col overflow-hidden bg-[#f3f5f7] lg:h-[90vh] lg:max-w-[460px] lg:rounded-3xl">
        <header class="flex items-center gap-3 border-b bg-white px-4 py-3">
          <button class="text-3xl" @click="close">‹</button>
          <div class="min-w-0 flex-1"><b>一家卡券充值店</b><p class="text-xs text-orange-500">真实体验分5.0 ★★★★★</p></div>
          <button class="rounded-full border px-3 py-1" @click="goShop">店铺</button><button @click="soon">•••</button>
        </header>
        <main ref="list" class="min-h-0 flex-1 space-y-4 overflow-y-auto p-4">
          <p class="text-center text-xs text-gray-400">{{ status }}</p>
          <div class="rounded-xl bg-white/70 p-3 text-sm text-gray-600">欢迎您光临本店<br>欢迎光临，请问有什么可以帮您！</div>
          <div v-for="message in messages" :key="message.id" class="flex gap-2" :class="message.sender === 'buyer' ? 'flex-row-reverse' : ''">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-orange-100 text-sm">{{ message.sender === 'buyer' ? buyerInitial : '客' }}</div>
            <div class="max-w-[75%]"><p class="mb-1 text-xs text-gray-400" :class="message.sender === 'buyer' ? 'text-right' : ''">{{ message.sender === 'buyer' ? buyerName : '店铺客服' }}</p><p class="rounded-2xl px-3 py-2 text-sm shadow-sm" :class="message.sender === 'buyer' ? 'bg-orange-500 text-white' : 'bg-white'">{{ message.content }}</p></div>
          </div>
        </main>
        <footer class="border-t bg-white p-3 pb-[max(12px,env(safe-area-inset-bottom))]">
          <div class="mb-2 flex gap-4 text-xs"><button v-for="x in ['评价客服','猜你喜欢','店铺上新']" :key="x" @click="soon">{{x}}</button></div>
          <form class="flex items-center gap-2" @submit.prevent="send"><button type="button" @click="soon">🎙</button><input v-model="draft" class="min-w-0 flex-1 rounded-lg bg-gray-100 px-3 py-2 text-sm outline-none" placeholder="输入消息"><button type="button" @click="soon">😊</button><button type="button" @click="soon">🛍</button><button v-if="draft.trim()" class="rounded bg-orange-500 px-2 py-1 text-xs text-white">发送</button><button v-else type="button" @click="more=!more">＋</button></form>
          <div v-if="more" class="grid grid-cols-4 gap-3 pt-4 text-center"><button v-for="x in tools" :key="x" class="rounded-xl bg-gray-100 p-3 text-xs" @click="soon">{{x}}</button></div>
        </footer>
      </section>
    </div>
  </Teleport>
</template>
<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useUserAuthStore } from '../stores/userAuth'
type Message={id:number|string;sender:'buyer'|'seller';content:string}
const apiBase=import.meta.env.VITE_PUBLIC_CHAT_API_URL||'https://kefu.cn12.vip/index.php/chn/koshopchat/public'
const auth=useUserAuthStore(),router=useRouter(),open=ref(false),more=ref(false),draft=ref(''),messages=ref<Message[]>([]),status=ref('正在连接店铺客服…'),list=ref<HTMLElement|null>(null)
const tools=['拍摄','相册','订单','商品','价保','工单进度','打款','文件'],session=ref<{id:number;hash:string}|null>(null),timer=ref<number>()
const buyerName=computed(()=>auth.isAuthenticated?(auth.user?.nickname||auth.user?.email||`买家 ${auth.user?.id}`):'访客'),buyerInitial=computed(()=>buyerName.value.slice(0,1))
const request=async(action:string,options:RequestInit={})=>{const s=session.value;const url=new URL(`${apiBase}/${action}`);if(s){url.searchParams.set('id',String(s.id));url.searchParams.set('hash',s.hash)}const r=await fetch(url,{...options,headers:{'Content-Type':'application/json',...(options.headers||{})}});const data=await r.json().catch(()=>({message:'客服连接失败'}));if(!r.ok){const e:any=new Error(data.message||'客服连接失败');e.status=r.status;throw e}return data}
const scroll=async()=>{await nextTick();list.value?.scrollTo({top:list.value.scrollHeight})}
const load=async()=>{if(!session.value)return;try{const data=await request('messages');if(!data.ok)throw new Error(data.message||'消息同步失败');messages.value=data.items||[];status.value='店铺客服在线，您可以直接留言';await scroll()}catch(e:any){if(e.status===403||String(e.message).includes('会话无效')){localStorage.removeItem('koshop_public_chat');session.value=null;await start();return}status.value='消息同步失败，正在重试'}}
const start=async()=>{try{if(timer.value)clearInterval(timer.value);const saved=localStorage.getItem('koshop_public_chat');if(saved&&!session.value)session.value=JSON.parse(saved);if(!session.value){const data=await request('start',{method:'POST',body:JSON.stringify({name:buyerName.value,avatar:auth.user?.avatar_url||'',userId:auth.user?.id||''})});session.value=data.session;localStorage.setItem('koshop_public_chat',JSON.stringify(data.session))}await load();if(open.value)timer.value=window.setInterval(()=>void load(),2500)}catch{status.value='暂时无法连接客服，请稍后重试'}}
const show=()=>{open.value=true;void start()},close=()=>{open.value=false;more.value=false;if(timer.value)clearInterval(timer.value)},send=async()=>{const content=draft.value.trim();if(!content||!session.value)return;try{const data=await request('send',{method:'POST',body:JSON.stringify({content})});if(!data.ok||!data.message)throw new Error(data.message||'消息发送失败，请重试');messages.value.push(data.message);draft.value='';status.value='店铺客服在线，您可以直接留言';await scroll()}catch(e:any){status.value=e.message||'消息发送失败，请重试'}},soon=()=>alert('该功能即将上线'),goShop=()=>{close();void router.push('/')}
onMounted(()=>addEventListener('koshop:open-chat',show));onBeforeUnmount(()=>{removeEventListener('koshop:open-chat',show);close()})
</script>
