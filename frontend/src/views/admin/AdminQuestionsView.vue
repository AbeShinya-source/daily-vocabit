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

// Generate modal state
const showGenerateModal = ref(false)
const isGenerating = ref(false)
const generateForm = ref({
  difficulty: 1,
  count: 10,
  date: new Date().toISOString().slice(0, 10),
})
const generateResult = ref(null)
const generateError = ref(null)
const generateProgress = ref(0)
let progressInterval = null

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

function openGenerateModal() {
  generateForm.value = {
    difficulty: 1,
    count: 10,
    date: new Date().toISOString().slice(0, 10),
  }
  generateResult.value = null
  generateError.value = null
  showGenerateModal.value = true
}

function closeGenerateModal() {
  showGenerateModal.value = false
}

async function generateQuestions() {
  isGenerating.value = true
  generateError.value = null
  generateResult.value = null
  generateProgress.value = 0

  // Estimate ~2.5 seconds per question
  const estimatedTime = generateForm.value.count * 2500
  const startTime = Date.now()

  // Simulate progress
  progressInterval = setInterval(() => {
    const elapsed = Date.now() - startTime
    const progress = Math.min(95, (elapsed / estimatedTime) * 100)
    generateProgress.value = Math.round(progress)
  }, 200)

  try {
    const response = await adminApi.generateQuestions({
      difficulty: Number(generateForm.value.difficulty),
      count: Number(generateForm.value.count),
      date: generateForm.value.date,
    })

    clearInterval(progressInterval)
    generateProgress.value = 100

    if (response.success) {
      generateResult.value = response.data
      // Reload dates and questions
      await loadDates()
      selectedDate.value = generateForm.value.date
      await loadQuestions()
    } else {
      generateError.value = response.error || '生成に失敗しました'
    }
  } catch (e) {
    clearInterval(progressInterval)
    generateError.value = e.message || '生成に失敗しました'
  } finally {
    isGenerating.value = false
  }
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
      <button class="generate-btn" @click="openGenerateModal">
        + 問題を生成
      </button>
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

    <!-- Generate Modal -->
    <div v-if="showGenerateModal" class="modal-overlay" @click.self="closeGenerateModal">
      <div class="modal">
        <div class="modal-header">
          <h2>問題を生成</h2>
          <button class="modal-close" @click="closeGenerateModal">&times;</button>
        </div>

        <div class="modal-body">
          <div v-if="generateResult" class="generate-result success">
            <h3>生成完了</h3>
            <div class="result-stats">
              <div class="stat-item">
                <span class="stat-label">生成成功</span>
                <span class="stat-value">{{ generateResult.saved_count }}問</span>
              </div>
              <div class="stat-item">
                <span class="stat-label">生成失敗</span>
                <span class="stat-value">{{ generateResult.failed_count }}問</span>
              </div>
              <div class="stat-item">
                <span class="stat-label">処理時間</span>
                <span class="stat-value">{{ generateResult.processing_time }}秒</span>
              </div>
              <div class="stat-item">
                <span class="stat-label">合計問題数</span>
                <span class="stat-value">{{ generateResult.total_count }}問</span>
              </div>
            </div>
            <button class="btn-primary" @click="closeGenerateModal">閉じる</button>
          </div>

          <form v-else @submit.prevent="generateQuestions">
            <div class="form-group">
              <label>日付</label>
              <input type="date" v-model="generateForm.date" required />
            </div>

            <div class="form-group">
              <label>難易度</label>
              <select v-model="generateForm.difficulty" required>
                <option :value="1">基礎 (Standard)</option>
                <option :value="2">上級 (Hard)</option>
              </select>
            </div>

            <div class="form-group">
              <label>生成数</label>
              <input
                type="number"
                v-model="generateForm.count"
                min="1"
                max="20"
                required
              />
              <span class="form-hint">1〜20問</span>
            </div>

            <div v-if="generateError" class="generate-error">
              {{ generateError }}
            </div>

            <div v-if="isGenerating" class="progress-section">
              <div class="progress-bar">
                <div class="progress-fill" :style="{ width: generateProgress + '%' }"></div>
              </div>
              <div class="progress-text">
                生成中... {{ generateProgress }}%
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" class="btn-secondary" @click="closeGenerateModal" :disabled="isGenerating">
                キャンセル
              </button>
              <button type="submit" class="btn-primary" :disabled="isGenerating">
                {{ isGenerating ? '生成中...' : '生成する' }}
              </button>
            </div>
          </form>
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

/* Generate Button */
.generate-btn {
  margin-left: auto;
  padding: 0.5rem 1rem;
  background: linear-gradient(135deg, #738ba8 0%, #5b7a9f 100%);
  color: white;
  border: none;
  border-radius: 0.375rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.generate-btn:hover {
  background: linear-gradient(135deg, #5b7a9f 0%, #4a6785 100%);
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: white;
  border-radius: 0.75rem;
  width: 90%;
  max-width: 450px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h2 {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e293b;
}

.modal-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #94a3b8;
  cursor: pointer;
  line-height: 1;
}

.modal-close:hover {
  color: #64748b;
}

.modal-body {
  padding: 1.25rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: #475569;
  margin-bottom: 0.375rem;
}

.form-group input,
.form-group select {
  width: 100%;
  padding: 0.625rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.375rem;
  font-size: 0.9375rem;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #738ba8;
  box-shadow: 0 0 0 3px rgba(115, 139, 168, 0.1);
}

.form-hint {
  font-size: 0.75rem;
  color: #94a3b8;
  margin-top: 0.25rem;
  display: block;
}

.modal-actions {
  display: flex;
  gap: 0.75rem;
  margin-top: 1.5rem;
}

.btn-primary,
.btn-secondary {
  flex: 1;
  padding: 0.75rem 1rem;
  border-radius: 0.375rem;
  font-size: 0.9375rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary {
  background: linear-gradient(135deg, #738ba8 0%, #5b7a9f 100%);
  color: white;
  border: none;
}

.btn-primary:hover:not(:disabled) {
  background: linear-gradient(135deg, #5b7a9f 0%, #4a6785 100%);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: white;
  color: #475569;
  border: 1px solid #e2e8f0;
}

.btn-secondary:hover:not(:disabled) {
  background: #f8fafc;
}

.btn-secondary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.generate-error {
  background: #fef2f2;
  color: #dc2626;
  padding: 0.75rem;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  margin-bottom: 1rem;
}

.progress-section {
  margin-bottom: 1rem;
}

.progress-bar {
  height: 0.5rem;
  background: #e2e8f0;
  border-radius: 0.25rem;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(135deg, #738ba8 0%, #5b7a9f 100%);
  border-radius: 0.25rem;
  transition: width 0.2s ease;
}

.progress-text {
  font-size: 0.8125rem;
  color: #64748b;
  text-align: center;
  margin-top: 0.5rem;
}

.generate-result {
  text-align: center;
}

.generate-result h3 {
  font-size: 1.125rem;
  font-weight: 600;
  color: #166534;
  margin-bottom: 1rem;
}

.result-stats {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.stat-item {
  background: #f8fafc;
  padding: 0.75rem;
  border-radius: 0.375rem;
}

.stat-label {
  display: block;
  font-size: 0.75rem;
  color: #64748b;
  margin-bottom: 0.25rem;
}

.stat-value {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e293b;
}
</style>
