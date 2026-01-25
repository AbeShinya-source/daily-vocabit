// src/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import TitleView from '@/views/TitleView.vue'
import QuizView from '@/views/QuizView.vue'
import ResultView from '@/views/ResultView.vue'
import AuthView from '@/views/AuthView.vue'
import MyPageView from '@/views/MyPageView.vue'
import SessionResultView from '@/views/SessionResultView.vue'
import BadgesView from '@/views/BadgesView.vue'
import AdminDashboardView from '@/views/admin/AdminDashboardView.vue'
import AdminQuestionsView from '@/views/admin/AdminQuestionsView.vue'
import AdminVocabulariesView from '@/views/admin/AdminVocabulariesView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  scrollBehavior(to, from, savedPosition) {
    // ブラウザの戻る/進むボタンを使った場合は保存された位置を使用
    if (savedPosition) {
      return savedPosition
    }
    // それ以外は常にトップにスクロール
    return { top: 0 }
  },
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
    // 管理画面
    {
      path: '/admin',
      name: 'AdminDashboard',
      component: AdminDashboardView,
    },
    {
      path: '/admin/questions',
      name: 'AdminQuestions',
      component: AdminQuestionsView,
    },
    {
      path: '/admin/vocabularies',
      name: 'AdminVocabularies',
      component: AdminVocabulariesView,
    },
  ],
})

export default router
