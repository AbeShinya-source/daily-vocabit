import './style.css'
import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import { useAuthStore } from './stores/auth'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

// 認証状態の初期化
const authStore = useAuthStore()
authStore.initAuth().then(() => {
  app.mount('#app')
})
