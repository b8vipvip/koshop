<template>
  <main class="login-page">
    <form class="login-card" @submit.prevent="submit">
      <div class="logo">K</div>
      <h1>Koshop 卖家中心</h1>
      <p>使用 Dujiao Admin 账号登录</p>
      <label>用户名<input v-model.trim="username" autocomplete="username" autofocus></label>
      <label>密码<input v-model="password" type="password" autocomplete="current-password"></label>
      <p v-if="message" class="login-error">{{message}}</p>
      <button :disabled="loading">{{loading?'登录中...':'登录'}}</button>
    </form>
  </main>
</template>

<script setup lang="ts">
import {ref} from 'vue'
import {authMessage,checkAuth,currentUser} from '../auth'
import {navigate} from '../router'

const username=ref('')
const password=ref('')
const loading=ref(false)
const message=ref(authMessage.value)

async function submit(){
  message.value=''
  if(!username.value||!password.value){
    message.value='请输入用户名和密码'
    return
  }

  loading.value=true
  try{
    const r=await fetch('/api/koshop-seller/auth/login',{
      method:'POST',
      credentials:'include',
      headers:{'content-type':'application/json'},
      body:JSON.stringify({username:username.value,password:password.value})
    })
    const j=await r.json()
    if(!r.ok||!j.ok)throw Error(j.message)

    currentUser.value=j.user
    authMessage.value=''
    password.value=''

    await checkAuth()
    navigate('/')
  }catch(e:any){
    message.value=e.message||'登录失败'
  }finally{
    loading.value=false
  }
}
</script>
