import { createApp } from 'vue'; import App from './App.vue'; import './style.css'; import {checkAuth} from './auth'; createApp(App).mount('#app'); checkAuth()
