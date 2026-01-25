<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { adminApi } from '@/api/client'

const router = useRouter()
const auth = useAuthStore()

const questions = ref([])
const dates = ref([])
const selectedDate = ref('')
const selectedDifficulty = ref('')
const total = ref(0)
const isLoading = ref(true)
const error = ref(null)
const expandedId = ref(null)

async function loadDates() {
  try {
    const response = await adminApi.getQuestionDates()
    dates.value = response.dates
    if (dates.value.length > 0) {
      selectedDate.value = dates.value[0].date
    }
  } catch (e) {
    error.value = e.message
  }
}

async function loadQuestions() {
  if (!selectedDate.value) return

  isLoading.value = true
  error.value = null

  try {
    const response = await adminApi.getQuestions(selectedDate.value, selectedDifficulty.value || null)
    questions.value = response.questions
    total.value = response.total
  } catch (e) {
    error.value = e.message
  } finally {
    isLoading.value = false
  }
}

function toggleExpand(id) {
  expandedId.value = expandedId.value === id ? null : id
}

function getDifficultyLabel(difficulty) {
  const labels = { 1: '基礎', 2: '上級', 3: '超上級' }
  return labels[difficulty] || difficulty
}

function getChoiceLetter(index) {
  return String.fromCharCode(65 + index)
}

watch([selectedDate, selectedDifficulty], () => {
  loadQuestions()
})

onMounted(() => {
  if (!auth.isAdmin) {
    router.push({ name: 'Title' })
    return
  }
  loadDates()
})
</script>

<template>
  <div class="admin-questions">
    <header class="page-header">
      <button class="back-btn" @click="router.push({ name: 'AdminDashboard' })">
        &larr; 戻る
      </button>
      <h1>問題一覧</h1>
    </header>

    <div class="filters">
      <div class="filter-group">
        <label>日付</label>
        <select v-model="selectedDate">
          <option v-for="d in dates" :key="d.date" :value="d.date">
            {{ d.date }} ({{ d.count }}問)
          </option>
        </select>
      </div>
      <div class="filter-group">
        <label>難易度</label>
        <select v-model="selectedDifficulty">
          <option value="">すべて</option>
          <option value="1">基礎</option>
          <option value="2">上級</option>
          <option value="3">超上級</option>
        </select>
      </div>
    </div>

    <div class="total-count">{{ total }}件</div>

    <div v-if="isLoading" class="loading">読み込み中...</div>

    <div v-else-if="error" class="error">{{ error }}</div>

    <div v-else class="questions-list">
      <div
        v-for="q in questions"
        :key="q.id"
        class="question-card"
        :class="{ expanded: expandedId === q.id }"
      >
        <div class="question-header" @click="toggleExpand(q.id)">
          <div class="question-meta">
            <span class="question-id">#{{ q.id }}</span>
            <span class="difficulty" :class="'diff-' + q.difficulty">
              {{ getDifficultyLabel(q.difficulty) }}
            </span>
          </div>
          <div class="question-text">{{ q.question_text }}</div>
          <div class="expand-icon">{{ expandedId === q.id ? '▲' : '▼' }}</div>
        </div>

        <div v-if="expandedId === q.id" class="question-detail">
          <div class="detail-row">
            <span class="label">日本語訳:</span>
            <span>{{ q.question_translation }}</span>
          </div>

          <div class="choices">
            <div
              v-for="(choice, index) in q.choices"
              :key="index"
              class="choice"
              :class="{ correct: index === q.correct_index }"
            >
              {{ getChoiceLetter(index) }}. {{ choice }}
              <span v-if="index === q.correct_index" class="correct-mark">正解</span>
            </div>
          </div>

          <div class="detail-row">
            <span class="label">解説:</span>
            <div class="explanation">{{ q.explanation }}</div>
          </div>

          <div v-if="q.vocabulary" class="vocabulary-info">
            <span class="label">語彙:</span>
            <span>{{ q.vocabulary.word }} - {{ q.vocabulary.meaning }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.admin-questions {
  max-width: 900px;
  margin: 0 auto;
  padding: 1rem;
}

.page-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.back-btn {
  padding: 0.5rem 1rem;
  background: #f1f5f9;
  border: none;
  border-radius: 0.375rem;
  cursor: pointer;
}

.back-btn:hover {
  background: #e2e8f0;
}

.page-header h1 {
  font-size: 1.5rem;
  color: #1e293b;
}

.filters {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.filter-group label {
  font-size: 0.875rem;
  color: #64748b;
}

.filter-group select {
  padding: 0.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.375rem;
  min-width: 150px;
}

.total-count {
  color: #64748b;
  margin-bottom: 1rem;
}

.loading, .error {
  text-align: center;
  padding: 2rem;
}

.error {
  color: #ef4444;
}

.questions-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.question-card {
  background: white;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.question-header {
  padding: 1rem;
  cursor: pointer;
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

.question-header:hover {
  background: #f8fafc;
}

.question-meta {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  min-width: 60px;
}

.question-id {
  font-size: 0.75rem;
  color: #94a3b8;
}

.difficulty {
  font-size: 0.75rem;
  padding: 0.125rem 0.375rem;
  border-radius: 0.25rem;
  text-align: center;
}

.diff-1 { background: #dcfce7; color: #166534; }
.diff-2 { background: #fef3c7; color: #92400e; }
.diff-3 { background: #fee2e2; color: #991b1b; }

.question-text {
  flex: 1;
  font-size: 0.95rem;
  color: #1e293b;
}

.expand-icon {
  color: #94a3b8;
  font-size: 0.75rem;
}

.question-detail {
  padding: 1rem;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
}

.detail-row {
  margin-bottom: 1rem;
}

.detail-row .label {
  font-weight: 600;
  color: #475569;
  display: block;
  margin-bottom: 0.25rem;
}

.choices {
  margin: 1rem 0;
}

.choice {
  padding: 0.5rem;
  margin-bottom: 0.25rem;
  border-radius: 0.25rem;
  background: white;
  border: 1px solid #e2e8f0;
}

.choice.correct {
  background: #dcfce7;
  border-color: #86efac;
}

.correct-mark {
  float: right;
  font-size: 0.75rem;
  color: #166534;
  font-weight: 600;
}

.explanation {
  white-space: pre-wrap;
  font-size: 0.9rem;
  color: #475569;
  line-height: 1.6;
}

.vocabulary-info {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
  font-size: 0.9rem;
}

.vocabulary-info .label {
  font-weight: 600;
  color: #475569;
}

@media (max-width: 640px) {
  .filters {
    flex-direction: column;
  }

  .filter-group select {
    width: 100%;
  }
}
</style>
