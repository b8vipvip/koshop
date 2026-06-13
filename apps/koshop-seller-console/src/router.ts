import {computed,ref} from 'vue'
const path=ref(location.pathname)
addEventListener('popstate',()=>path.value=location.pathname)
export const route=computed(()=>({path:path.value,chatId:path.value.match(/^\/messages\/([^/]+)/)?.[1]}))
export const primaryPaths=['/','/messages','/orders','/products','/finance','/settings']
export const showBottomNav=computed(()=>primaryPaths.includes(path.value))
export function navigate(to:string){history.pushState({},'',to);path.value=to;scrollTo(0,0)}
export function goBack(fallback:string){if(history.length>1)history.back();else navigate(fallback)}
