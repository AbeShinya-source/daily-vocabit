<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { adminApi } from '@/api/client'

const router = useRouter()
const auth = useAuthStore()

const dashboard = ref(null)
const isLoading = ref(true)
const error = ref(null)

async function loadDashboard() {
  isLoading.value = true
  error.value = null

  try {
    const response = await adminApi.getDashboard()
    dashboard.value = response
  } catch (e) {
    error.value = e.message
  } finally {
    isLoading.value = false
  }
}

function goToQuestions() {
  router.push({ name: 'AdminQuestions' })
}

function goToVocabularies() {
  router.push({ name: 'AdminVocabularies' })
}

function logout() {
  auth.logout()
  router.push({ name: 'Title' })
}

onMounted(() => {
  if (!auth.isAdmin) {
    router.push({ name: 'Title' })
    return
  }
  loadDashboard()
})
</script>

<template>
  <div class="admin-dashboard">
    <header class="admin-header">
      <h1>管理画面</h1>
      <button class="logout-btn" @click="logout">ログアウト</button>
    </header>

    <div v-if="isLoading" class="loading">読み込み中...</div>

    <div v-else-if="error" class="error">{{ error }}</div>

    <div v-else-if="dashboard" class="dashboard-content">
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-value">{{ dashboard.total_vocabularies }}</div>
          <div class="stat-label">総語彙数</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ dashboard.total_questions }}</div>
          <div class="stat-label">総問題数</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ dashboard.today_questions }}</div>
          <div class="stat-label">今日の問題数</div>
        </div>
      </div>

      <div class="detail-section">
        <h2>難易度別語彙数</h2>
        <div class="detail-grid">
          <div class="detail-item">
            <span class="label">基礎</span>
            <span class="value">{{ dashboard.vocabularies_by_difficulty[1] }}</span>
          </div>
          <div class="detail-item">
            <span class="label">上級</span>
            <span class="value">{{ dashboard.vocabularies_by_difficulty[2] }}</span>
          </div>
          <div class="detail-item">
            <span class="label">超上級</span>
            <span class="value">{{ dashboard.vocabularies_by_difficulty[3] }}</span>
          </div>
        </div>
      </div>

      <div class="detail-section">
        <h2>タイプ別語彙数</h2>
        <div class="detail-grid">
          <div class="detail-item">
            <span class="label">単語</span>
            <span class="value">{{ dashboard.vocabularies_by_type.WORD }}</span>
          </div>
          <div class="detail-item">
            <span class="label">イディオム</span>
            <span class="value">{{ dashboard.vocabularies_by_type.IDIOM }}</span>
          </div>
        </div>
      </div>

      <div class="action-buttons">
        <button class="action-btn" @click="goToQuestions">問題一覧</button>
        <button class="action-btn primary" @click="goToVocabularies">語彙管理</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.admin-dashboard {
  max-width: 800px;
  margin: 0 auto;
  padding: 1rem;
}

.admin-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e2e8f0;
}

.admin-header h1 {
  font-size: 1.5rem;
  color: #1e293b;
}

.logout-btn {
  padding: 0.5rem 1rem;
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 0.375rem;
  cursor: pointer;
}

.logout-btn:hover {
  background: #dc2626;
}

.loading, .error {
  text-align: center;
  padding: 2rem;
}

.error {
  color: #ef4444;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  border-radius: 0.5rem;
  padding: 1.5rem;
  text-align: center;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.stat-value {
  font-size: 2rem;
  font-weight: bold;
  color: #3b82f6;
}

.stat-label {
  color: #64748b;
  margin-top: 0.5rem;
}

.detail-section {
  background: white;
  border-radius: 0.5rem;
  padding: 1.5rem;
  margin-bottom: 1rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.detail-section h2 {
  font-size: 1rem;
  color: #475569;
  margin-bottom: 1rem;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem;
  background: #f8fafc;
  border-radius: 0.25rem;
}

.detail-item .label {
  color: #64748b;
}

.detail-item .value {
  font-weight: 600;
  color: #1e293b;
}

.action-buttons {
  display: flex;
  gap: 1rem;
  margin-top: 2rem;
}

.action-btn {
  flex: 1;
  padding: 1rem;
  border: 1px solid #e2e8f0;
  background: white;
  border-radius: 0.5rem;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.2s;
}

.action-btn:hover {
  background: #f8fafc;
}

.action-btn.primary {
  background: #3b82f6;
  color: white;
  border-color: #3b82f6;
}

.action-btn.primary:hover {
  background: #2563eb;
}

@media (max-width: 640px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .detail-grid {
    grid-template-columns: 1fr;
  }

  .action-buttons {
    flex-direction: column;
  }
}
</style>
