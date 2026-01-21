// src/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import TitleView from '@/views/TitleView.vue'
import QuizView from '@/views/QuizView.vue'
import ResultView from '@/views/ResultView.vue'
import AuthView from '@/views/AuthView.vue'
import MyPageView from '@/views/MyPageView.vue'
import SessionResultView from '@/views/SessionResultView.vue'
import BadgesView from '@/views/BadgesView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'Title',
      component: TitleView,
    },
    {
      path: '/quiz',
      name: 'Quiz',
      component: QuizView,
    },
    {
      path: '/result',
      name: 'Result',
      component: ResultView,
    },
    {
      path: '/auth',
      name: 'Auth',
      component: AuthView,
    },
    {
      path: '/mypage',
      name: 'MyPage',
      component: MyPageView,
    },
    {
      path: '/session/:id',
      name: 'SessionResult',
      component: SessionResultView,
    },
    {
      path: '/badges',
      name: 'Badges',
      component: BadgesView,
    },
  ],
})

export default router
