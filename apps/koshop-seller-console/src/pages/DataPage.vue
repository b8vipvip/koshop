<template>
  <div class="page data-page">
    <TopBar
      :title="isDetail ? detailTitle : title"
      :back="isDetail"
      :fallback="`/${kind}`"
    />

    <section v-if="!isDetail && kind!=='settings'" class="filters">
      <input v-model="keyword" :placeholder="placeholder" @keyup.enter="load">
      <select v-if="kind!=='finance'" v-model="status" @change="load">
        <option value="">全部状态</option>
        <option v-for="x in statuses" :key="x[1]" :value="x[1]">{{x[0]}}</option>
      </select>
      <button @click="load">查询</button>
    </section>

    <p v-if="loading" class="empty">加载中...</p>

    <section v-else-if="error" class="card error-state">
      <p>{{error}}</p>
      <button @click="load">重试</button>
    </section>

    <template v-else-if="isDetail">
      <section class="card detail nice-detail">
        <div v-if="detail?.cover" class="detail-cover">
          <img :src="detail.cover" alt="商品图片">
        </div>

        <h2>{{detailTitle}}</h2>

        <p v-for="row in detailRows" :key="row.key">
          <b>{{row.label}}</b>
          <span>{{row.value}}</span>
        </p>
      </section>
    </template>

    <template v-else-if="kind==='settings'">
      <section class="card detail">
        <h2>{{data.siteName || 'Dujiao-Next'}}</h2>
        <p><b>Dujiao API</b><span>{{data.integrations?.dujiao?'已连接':'未连接'}}</span></p>
        <p><b>LHC</b><span>{{data.integrations?.lhc?'已连接':'未连接'}}</span></p>
        <p><b>支付配置</b><span>{{data.paymentConfigured?'已配置':'未确认'}}</span></p>
        <p><b>发货配置</b><span>{{data.fulfillmentConfigured?'已配置':'未确认'}}</span></p>
      </section>
      <button class="logout" @click="logout">退出登录</button>
    </template>

    <template v-else>
      <section v-if="kind==='finance'" class="core summary">
        <article><span>今日收入</span><b>¥{{summary.todayIncome || '0.00'}}</b></article>
        <article><span>总收入</span><b>¥{{summary.totalIncome || '0.00'}}</b></article>
        <article><span>退款</span><b>¥{{summary.refundAmount || '0.00'}}</b></article>
      </section>

      <p v-if="!items.length" class="empty">{{empty}}</p>

      <section class="records product-records">
        <article v-for="x in items" :key="x.id" @click="open(x.id)">
          <img v-if="kind==='products' && x.cover" class="record-cover" :src="x.cover" alt="商品图">
          <div>
            <strong>{{recordTitle(x)}}</strong>
            <small>{{recordSub(x)}}</small>
          </div>
          <div>
            <b>¥{{x.price || x.payAmount || x.amount || '0.00'}}</b>
            <span>{{x.statusText}}</span>
            <small>{{x.createdAt}}</small>
          </div>
        </article>
      </section>
    </template>
  </div>
</template>

<script setup lang="ts">
import {computed,onMounted,ref,watch} from 'vue'
import TopBar from '../components/TopBar.vue'
import {navigate,route} from '../router'
import {logout} from '../auth'

const props=defineProps<{kind:string,title:string}>()

const loading=ref(false)
const error=ref('')
const items=ref<any[]>([])
const data=ref<any>({})
const detail=ref<any>()
const summary=ref<any>({})
const keyword=ref('')
const status=ref('')

const statuses=[
  ['待付款','pending_payment'],
  ['待发货','pending_delivery'],
  ['已发货','delivered'],
  ['已完成','completed'],
  ['已关闭','closed'],
  ['上架','on_sale'],
  ['下架','off_sale']
]

const empty=props.kind==='orders'?'暂无订单':props.kind==='products'?'暂无商品':'暂无财务记录'
const placeholder=computed(()=>props.kind==='products'?'搜索商品名称':'搜索订单号、买家或商品')
const isDetail=computed(()=>Boolean(route.value.path.match(new RegExp(`^/${props.kind}/([^/]+)$`))))

const detailTitle=computed(()=>{
  const d=detail.value||{}
  if(props.kind==='products')return d.title||`商品 #${d.id||''}`
  if(props.kind==='orders')return d.orderNo||`订单 #${d.id||''}`
  return props.title
})

const detailRows=computed(()=>{
  const d=detail.value||{}
  const map:any=props.kind==='products'
    ? {
        id:'ID',
        title:'商品名称',
        price:'价格',
        stock:'库存',
        statusText:'状态',
        categoryName:'分类',
        purchaseType:'购买类型',
        fulfillmentType:'发货类型',
        description:'商品描述',
        createdAt:'创建时间',
        updatedAt:'更新时间'
      }
    : {
        id:'ID',
        orderNo:'订单号',
        buyerName:'买家',
        buyerContact:'联系方式',
        productTitle:'商品',
        payAmount:'支付金额',
        statusText:'状态',
        deliveryStatus:'发货状态',
        createdAt:'创建时间',
        paidAt:'支付时间',
        remark:'备注'
      }

  return Object.entries(map)
    .map(([key,label])=>({key,label,value:formatValue(d[key])}))
    .filter(x=>x.value!==''&&x.value!==undefined&&x.value!==null)
})

function formatValue(v:any){
  if(v===undefined||v===null)return ''
  if(typeof v==='boolean')return v?'是':'否'
  if(typeof v==='number')return String(v)
  if(typeof v==='string')return v
  if(Array.isArray(v))return v.length?`${v.length} 项`:''
  if(typeof v==='object'){
    return v['zh-CN']||v['zh_CN']||v.zh||v['zh-TW']||v['en-US']||''
  }
  return String(v)
}

function recordTitle(x:any){
  if(props.kind==='products')return x.title || `商品 #${x.id}`
  if(props.kind==='orders')return x.orderNo || `订单 #${x.id}`
  return x.typeText || '记录'
}

function recordSub(x:any){
  if(props.kind==='products')return [x.categoryName, `库存 ${x.stock ?? 0}`].filter(Boolean).join(' · ')
  if(props.kind==='orders')return [x.productTitle, x.buyerName].filter(Boolean).join(' · ')
  return x.channel || ''
}

async function load(){
  loading.value=true
  error.value=''
  try{
    const id=route.value.path.match(new RegExp(`^/${props.kind}/([^/]+)$`))?.[1]
    const q=new URLSearchParams({page:'1',pageSize:'20',keyword:keyword.value,status:status.value})
    const url=`/api/koshop-seller/${props.kind}${id?'/'+id:'?'+q}`
    const r=await fetch(url,{credentials:'include'})
    const j=await r.json()
    if(!r.ok||!j.ok)throw Error(j.message)
    if(id)detail.value=j.data
    else if(props.kind==='settings')data.value=j.data
    else{
      items.value=j.items||[]
      summary.value=j.summary||{}
    }
  }catch(e:any){
    error.value=e.message||'Dujiao Admin 接口暂不可用'
  }finally{
    loading.value=false
  }
}

function open(id:any){
  if(['orders','products'].includes(props.kind))navigate(`/${props.kind}/${id}`)
}

watch(()=>route.value.path,()=>load())
onMounted(load)
</script>
