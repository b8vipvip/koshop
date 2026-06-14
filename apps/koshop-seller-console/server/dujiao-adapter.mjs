const pick=(o,...ks)=>ks.map(k=>o?.[k]).find(v=>v!==undefined&&v!==null)

function localize(v){
  if(v===undefined||v===null)return ''
  if(typeof v==='string'||typeof v==='number'||typeof v==='boolean')return String(v)
  if(Array.isArray(v))return v.map(localize).filter(Boolean).join(' / ')
  if(typeof v==='object'){
    return v['zh-CN']||v['zh_CN']||v.zh||v['zh-TW']||v['zh_TW']||v['en-US']||v.en||Object.values(v).find(x=>typeof x==='string'&&x.trim())||''
  }
  return String(v)
}

const money=v=>{
  const n=Number(v??0)
  return Number.isFinite(n)?n.toFixed(2):'0.00'
}

const time=v=>{
  if(!v)return ''
  const s=String(v)
  if(/Z$|T/.test(s)){
    const d=new Date(s)
    if(!Number.isNaN(d.getTime()))return d.toLocaleString('zh-CN',{timeZone:'Asia/Shanghai',hour12:false})
  }
  return s
}

export function normalizeStatus(v){
  v=String(v??'').toLowerCase()
  return ({
    pending:'pending_payment',
    unpaid:'pending_payment',
    pending_payment:'pending_payment',
    paid:'paid',
    processing:'pending_delivery',
    pending_delivery:'pending_delivery',
    delivered:'delivered',
    completed:'completed',
    closed:'closed',
    cancelled:'closed',
    canceled:'closed',
    refunded:'refunded'
  })[v]||v||'pending_payment'
}

const statusText=s=>({
  pending_payment:'待付款',
  paid:'已支付',
  pending_delivery:'待发货',
  delivered:'已发货',
  completed:'已完成',
  closed:'已关闭',
  refunded:'已退款'
})[s]||s

export function normalizePagination(raw={},fallback={}){
  const p=raw.pagination||raw.meta||raw
  return {
    page:Number(p.page||fallback.page||1),
    pageSize:Number(p.page_size||p.pageSize||fallback.pageSize||20),
    total:Number(p.total||p.total_count||0)
  }
}

export function unwrap(raw){
  return raw?.data?.data??raw?.data??raw
}

export function listOf(raw){
  const d=unwrap(raw)
  return d?.items||d?.list||d?.records||(Array.isArray(d)?d:[])
}

function productStock(x){
  const manual=Number(x.manual_stock_total??0)-Number(x.manual_stock_locked??0)-Number(x.manual_stock_sold??0)
  const auto=Number(x.auto_stock_available??0)
  const direct=Number(pick(x,'stock','available_stock','inventory')??NaN)
  if(Number.isFinite(direct))return direct
  return Math.max(0, manual) + Math.max(0, auto)
}

function firstImage(x){
  const imgs=x.images
  if(Array.isArray(imgs)&&imgs.length)return imgs[0]
  return pick(x,'cover','image','main_image','thumbnail')||''
}

export function normalizeProduct(x={}){
  const active=pick(x,'is_active','active')
  const status=active===undefined
    ? (String(x.status||'').includes('off')?'off_sale':'on_sale')
    : (active?'on_sale':'off_sale')

  return {
    id:pick(x,'id','ID'),
    title:localize(pick(x,'title','name','goods_name','product_name'))||`商品 #${pick(x,'id','ID')||''}`,
    description:localize(pick(x,'description','content','detail')),
    price:money(pick(x,'price_amount','price','amount','sale_price')),
    costPrice:money(pick(x,'cost_price_amount','cost_price')),
    stock:productStock(x),
    status,
    statusText:status==='on_sale'?'上架':'下架',
    cover:firstImage(x),
    categoryName:localize(pick(x,'category_name','categoryName')||x.category?.name),
    category:x.category||null,
    skus:x.skus||x.variants||[],
    purchaseType:x.purchase_type||'',
    fulfillmentType:x.fulfillment_type||'',
    minPurchaseQuantity:x.min_purchase_quantity,
    maxPurchaseQuantity:x.max_purchase_quantity,
    createdAt:time(pick(x,'created_at','create_time','createdAt')),
    updatedAt:time(pick(x,'updated_at','update_time','updatedAt'))
  }
}

export function normalizeOrder(x={}){
  const status=normalizeStatus(pick(x,'status','order_status'))
  const buyer=x.user||x.buyer||{}
  const firstItem=x.items?.[0]||{}
  return {
    id:pick(x,'id','ID'),
    orderNo:pick(x,'order_no','order_sn','orderNo','trade_no')||'',
    buyerName:pick(x,'buyer_name','buyerName')||x.user_display_name||buyer.display_name||buyer.username||buyer.email||'买家',
    buyerContact:pick(x,'buyer_contact','email','phone')||x.user_email||buyer.email||buyer.phone||'',
    productTitle:localize(pick(x,'product_title','goods_name','product_name')||x.product?.title||firstItem.product_title||firstItem.title),
    amount:money(pick(x,'total_amount','amount','price')),
    payAmount:money(pick(x,'online_paid_amount','wallet_paid_amount','paid_amount','pay_amount','total_amount','amount')),
    status,
    statusText:statusText(status),
    payStatus:pick(x,'pay_status','payment_status')||(['paid','pending_delivery','delivered','completed'].includes(status)?'paid':'pending'),
    deliveryStatus:pick(x,'delivery_status','fulfillment_status')||(['delivered','completed'].includes(status)?'delivered':'pending'),
    createdAt:time(pick(x,'created_at','create_time','createdAt')),
    paidAt:time(pick(x,'paid_at','payment_time','paidAt')),
    buyer,
    items:x.items||[],
    payments:x.payments||[],
    fulfillment:x.fulfillment||x.fulfillments||null,
    logs:x.logs||[],
    remark:pick(x,'remark','note','admin_note')||''
  }
}

export function normalizeFinanceItem(x={}){
  const ok=['success','paid','completed'].includes(String(pick(x,'status','payment_status')).toLowerCase())
  return {
    id:pick(x,'id','ID'),
    orderNo:pick(x,'order_no','order_sn','trade_no')||x.order?.order_no||'',
    type:'payment',
    typeText:'支付',
    amount:money(pick(x,'amount','pay_amount','total_amount')),
    channel:pick(x,'channel_name','channel','payment_method')||x.channel?.name||'',
    status:ok?'success':String(x.status||'pending'),
    statusText:ok?'成功':(x.status||'处理中'),
    createdAt:time(pick(x,'created_at','create_time','createdAt'))
  }
}

export function normalizeDashboard(raw={}){
  const d=unwrap(raw)||{}
  return {
    todayPaidAmount:Number(pick(d,'today_revenue','today_paid_amount')||0),
    todayOrders:Number(pick(d,'today_orders')||0),
    todayVisitors:0,
    pendingPayment:Number(pick(d,'pending_orders','pending_payment_orders')||0),
    pendingDelivery:Number(pick(d,'processing_orders','pending_delivery')||0),
    afterSale:Number(pick(d,'after_sale','refund_orders')||0),
    lowStock:Number(pick(d,'low_stock_products')||0),
    recentOrders:(d.recent_orders||[]).map(normalizeOrder),
    updatedAt:new Date().toLocaleString('zh-CN',{timeZone:'Asia/Shanghai',hour12:false})
  }
}

export function safeSettings(raw={}){
  const d=unwrap(raw)||{}
  const vals=Array.isArray(d)?Object.fromEntries(d.map(x=>[x.key,x.value])):d
  return {
    siteName:vals.site_name||vals.siteName||'Dujiao-Next',
    siteLogo:vals.site_logo||vals.logo||'',
    customerService:vals.customer_service||vals.customer_service_url||'',
    paymentConfigured:Boolean(vals.payment_configured),
    fulfillmentConfigured:Boolean(vals.fulfillment_configured)
  }
}
