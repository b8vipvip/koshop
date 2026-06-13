<template><div class="shell"><aside :class="{open:menuOpen}"><div class="brand"><i>K</i><div><b>Koshop</b><small>卖家工作台</small></div></div><nav><button v-for="n in nav" :class="{active:page===n.key}" @click="select(n.key)"><component :is="n.icon"/><span>{{n.name}}</span><em v-if="n.badge">{{n.badge}}</em></button></nav><footer><span :class="{bad:!health.ok}"></span><div><b>{{health.ok?'系统服务正常':'服务待配置'}}</b><small>服务端 BFF · 安全代理</small></div></footer></aside>
<main><header><button class="mobile icon" @click="menuOpen=!menuOpen"><Menu/></button><div><h1>{{title}}</h1><p>{{subtitle}}</p></div><section><time>{{today}}</time><a class="icon" :href="adminUrl" target="_blank"><ExternalLink/></a><i>管</i></section></header>
<div class="content"><div class="welcome"><div><label>SELLER OPERATIONS</label><h2>{{page==='dashboard'?'今天也要及时处理订单和咨询':title}}</h2><p>{{subtitle}}</p></div><button class="primary" @click="load"><RefreshCw/>刷新数据</button></div><div v-if="error" class="warn"><AlertTriangle/>{{error}}<a :href="adminUrl" target="_blank">使用原后台兜底</a></div>
<div v-if="page==='dashboard'"><div class="metrics"><article v-for="m in metrics"><span :class="m.tone"><component :is="m.icon"/></span><div><small>{{m.name}}</small><b>{{m.value}}</b><em>{{m.hint}}</em></div></article></div><div class="grid"><article class="panel"><h3>最近订单</h3><DataTable :rows="rows('orders')" empty="暂无订单数据；配置 BFF 上游路径后自动显示。"/></article><article class="panel"><h3>优先待办</h3><button v-for="t in todos" @click="select(t.target)"><span></span>{{t.name}}<b>{{t.value}}</b><ChevronRight/></button></article></div></div>
<div v-else-if="page==='service'" class="service-card"><div><Headphones/><section><b>Koshop 客服接待工作台</b><small>真实 LHC 模块路由 · 非 iframe 集成</small></section><a class="primary" :href="lhcUrl" target="_blank"><ExternalLink/>打开三栏工作台</a></div><p>客服工作台使用 Live Helper Chat 登录、权限、会话与消息机制；卖家后台不保存客服密钥。</p><DataTable :rows="rows('chats')" empty="暂无可展示咨询，点击上方按钮进入实时工作台。"/></div>
<div v-else><article class="panel module-head"><div><h3>{{title}}</h3><p>{{moduleHelp[page]}}</p></div><a class="primary" :href="adminLinks[page]" target="_blank"><ExternalLink/>原后台写操作</a></article><article class="panel table-panel"><DataTable :rows="rows(page)" :empty="emptyText[page]"/></article></div></div></main></div></template>
<script setup lang="ts">
import {computed,defineComponent,h,onMounted,ref} from 'vue'; import {AlertTriangle,ChevronRight,ClipboardList,CreditCard,ExternalLink,Headphones,LayoutDashboard,Menu,PackageCheck,RefreshCw,Settings,ShoppingBag,TriangleAlert,WalletCards} from 'lucide-vue-next';
const api=import.meta.env.VITE_SELLER_API_BASE||'/api/koshop-seller',adminUrl=import.meta.env.VITE_DUJIAO_ADMIN_URL||'https://admin.cn12.vip',lhcUrl=import.meta.env.VITE_LHC_WORKBENCH_URL||'https://kefu.cn12.vip/index.php/site_admin/koshopchat/dashboard';
const page=ref('dashboard'),menuOpen=ref(false),error=ref(''),health=ref<any>({}),data=ref<Record<string,any>>({}); const today=new Intl.DateTimeFormat('zh-CN',{year:'numeric',month:'long',day:'numeric',weekday:'short'}).format(new Date()); const nav=[{key:'dashboard',name:'工作台首页',icon:LayoutDashboard},{key:'products',name:'商品管理',icon:ShoppingBag},{key:'orders',name:'订单管理',icon:ClipboardList,badge:'待办'},{key:'finance',name:'交易 / 财务',icon:WalletCards},{key:'service',name:'客服接待',icon:Headphones},{key:'settings',name:'系统设置',icon:Settings}];
const title=computed(()=>nav.find(n=>n.key===page.value)?.name||''),subtitle=computed(()=>page.value==='dashboard'?'商城经营数据、库存风险与客服接待集中处理。':page.value==='service'?'同时接待多个买家咨询，保留原客服后台兜底。':'读取操作走服务端代理，敏感写操作使用原后台兜底。'); const moduleHelp:any={products:'读取商品、状态与卡密库存摘要。',orders:'读取支付、发货与买家订单信息。',finance:'读取支付、充值与退款流水。',settings:'检查 Dujiao 与 Live Helper Chat 服务端连接状态。'}; const adminLinks:any={products:adminUrl+'/products',orders:adminUrl+'/orders',finance:adminUrl+'/payments',settings:adminUrl}; const emptyText:any={products:'暂无商品数据；请检查 Dujiao 商品 API 路径。',orders:'暂无订单数据；请检查 Dujiao 订单 API 路径。',finance:'暂无财务数据；当前 API 不可用时会明确显示。',settings:'服务状态见上方提示。'};
const unwrap=(v:any)=>v?.data?.data??v?.data??v??{}; const rows=(key:string)=>{if(key==='settings')return [{Dujiao:health.value?.integrations?.dujiao?'已配置':'未配置','Live Helper Chat':health.value?.integrations?.lhc?'已配置':'未配置'}];const v=unwrap(data.value[key]);return Array.isArray(v)?v:Array.isArray(v?.items)?v.items:Array.isArray(v?.list)?v.list:[]}; const pick=(obj:any,keys:string[],fallback='—')=>{for(const k of keys)if(obj?.[k]!==undefined)return obj[k];return fallback}; const dash=computed(()=>unwrap(data.value.dashboard)); const metrics=computed(()=>[{name:'今日订单数',value:pick(dash.value,['today_orders','orders_total']),hint:'今日创建订单',icon:ClipboardList,tone:'blue'},{name:'今日支付金额',value:pick(dash.value,['today_paid_amount','paid_amount']),hint:'支付成功金额',icon:CreditCard,tone:'green'},{name:'待处理订单',value:pick(dash.value,['pending_orders','processing_orders']),hint:'请及时处理',icon:PackageCheck,tone:'orange'},{name:'低库存商品',value:pick(dash.value,['low_stock_products']),hint:'建议及时补充',icon:TriangleAlert,tone:'red'},{name:'当前咨询',value:rows('chats').length||'—',hint:'客服系统返回',icon:Headphones,tone:'purple'}]); const todos=computed(()=>[{name:'待处理订单',value:metrics.value[2].value,target:'orders'},{name:'低库存商品',value:metrics.value[3].value,target:'products'},{name:'待回复咨询',value:metrics.value[4].value,target:'service'}]); function select(k:string){page.value=k;menuOpen.value=false;load(k)} async function get(k:string){const r=await fetch(`${api}/${k}`);const j=await r.json();if(!r.ok||j.ok===false)throw Error(j.message||`${k} 数据不可用`);data.value[k]=j} async function load(k=page.value){error.value='';try{health.value=await (await fetch(`${api}/health`)).json();if(k==='dashboard')await Promise.allSettled(['dashboard','orders','chats'].map(get));else if(k!=='settings'&&k!=='service')await get(k);else if(k==='service')await get('chats')}catch(e:any){error.value=e.message||'服务端代理暂时不可用'}} onMounted(()=>load());
const DataTable = defineComponent({
  props: {
    rows: { type: Array, required: true },
    empty: { type: String, default: '暂无数据' },
  },
  setup(props) {
    return () => {
      const tableRows = props.rows as Array<Record<string, unknown>>

      if (!tableRows.length) {
        return h('p', { class: 'empty' }, props.empty)
      }

      const headers = Object.keys(tableRows[0] || {}).slice(0, 6)

      return h('div', { class: 'data-table' }, [
        h('table', [
          h('thead', [
            h('tr', headers.map((key) => h('th', { key }, key))),
          ]),
          h('tbody', tableRows.slice(0, 12).map((row, index) =>
            h('tr', { key: index }, headers.map((key) => {
              const value = row[key]
              const text = typeof value === 'object' && value !== null
                ? JSON.stringify(value)
                : String(value ?? '—')
              return h('td', { key }, text)
            }))
          )),
        ]),
      ])
    }
  },
})

</script>
