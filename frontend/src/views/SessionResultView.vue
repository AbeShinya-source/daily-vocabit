<!-- src/views/SessionResultView.vue -->
<template>
  <div class="result-container">
    <!-- Header -->
    <header class="result-header">
      <button class="back-button" @click="goBack">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
      </button>
      <h1 class="page-title">学習結果</h1>
    </header>

    <!-- Loading State -->
    <div v-if="isLoading" class="loading-container">
      <div class="loading-spinner"></div>
      <p>読み込み中...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-container">
      <p>{{ error }}</p>
      <button class="retry-btn" @click="loadSession">再試行</button>
    </div>

    <!-- Result Content -->
    <div v-else-if="session" class="result-content">
      <!-- Score Card -->
      <section class="score-card">
        <div class="score-circle" :class="getAccuracyClass(session.accuracy)">
          <span class="score-value">{{ session.correctCount }}</span>
          <span class="score-divider">/</span>
          <span class="score-total">{{ session.totalQuestions }}</span>
        </div>
        <div class="score-info">
          <div class="score-percentage" :class="getAccuracyClass(session.accuracy)">
            {{ session.accuracy }}%
          </div>
          <div class="score-label">正答率</div>
        </div>
        <div class="session-meta">
          <span class="difficulty-badge" :class="session.difficulty === 1 ? 'standard' : 'hard'">
            {{ session.difficultyLabel }}
          </span>
          <span class="date-badge">{{ formatDate(session.completedAt) }}</span>
        </div>
      </section>

      <!-- Answers List -->
      <section class="answers-section">
        <h3 class="section-title">回答詳細</h3>
        <div class="answers-list">
          <div
            v-for="(answer, index) in answers"
            :key="answer.id"
            class="answer-item"
            :class="{ correct: answer.isCorrect, incorrect: !answer.isCorrect }"
          >
            <div class="answer-header">
              <span class="question-number">Q{{ index + 1 }}</span>
              <span class="answer-status">
                <svg v-if="answer.isCorrect" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <polyline points="20 6 9 17 4 12"/>
                </svg>
                <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <line x1="18" y1="6" x2="6" y2="18"/>
                  <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
              </span>
            </div>

            <div class="answer-content">
              <div class="vocabulary-info">
                <span class="word">{{ answer.vocabulary.word }}</span>
                <span class="meaning">{{ answer.vocabulary.meaning }}</span>
              </div>

              <div class="question-text">{{ answer.questionText }}</div>
              <div v-if="answer.questionTranslation" class="question-translation">
                {{ answer.questionTranslation }}
              </div>

              <div class="choices-list">
                <div
                  v-for="(choice, choiceIndex) in answer.choices"
                  :key="choiceIndex"
                  class="choice-item"
                  :class="{
                    'selected': choiceIndex === answer.selectedIndex,
                    'correct-choice': choiceIndex === answer.correctIndex,
                    'wrong-choice': choiceIndex === answer.selectedIndex && choiceIndex !== answer.correctIndex,
                  }"
                >
                  <span class="choice-marker">{{ ['A', 'B', 'C', 'D'][choiceIndex] }}</span>
                  <span class="choice-text">{{ choice }}</span>
                  <span v-if="choiceIndex === answer.correctIndex" class="choice-badge correct">正解</span>
                  <span v-if="choiceIndex === answer.selectedIndex && choiceIndex !== answer.correctIndex" class="choice-badge wrong">選択</span>
                </div>
              </div>

              <div v-if="answer.explanation" class="explanation">
                <div class="explanation-label">解説</div>
                <div class="explanation-text">{{ answer.explanation }}</div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Action Buttons -->
      <div class="action-buttons">
        <button class="action-btn secondary" @click="goToMyPage">マイページへ</button>
        <button class="action-btn primary" @click="goToTitle">もう一度学習する</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { statsApi } from '@/api/client'

const router = useRouter()
const route = useRoute()

const isLoading = ref(true)
const error = ref(null)
const session = ref(null)
const answers = ref([])

onMounted(() => {
  loadSession()
})

async function loadSession() {
  isLoading.value = true
  error.value = null

  try {
    const sessionId = route.params.id
    const res = await statsApi.getSessionDetail(sessionId)

    if (res.success) {
      session.value = res.data.session
      answers.value = res.data.answers
    } else {
      error.value = 'セッションが見つかりませんでした'
    }
  } catch (e) {
    console.error('Failed to load session:', e)
    error.value = 'データの読み込みに失敗しました'
  } finally {
    isLoading.value = false
  }
}

function getAccuracyClass(accuracy) {
  if (accuracy >= 80) return 'high'
  if (accuracy >= 60) return 'medium'
  return 'low'
}

function formatDate(isoString) {
  const date = new Date(isoString)
  return `${date.getFullYear()}/${date.getMonth() + 1}/${date.getDate()}`
}

function goBack() {
  router.push({ name: 'MyPage' })
}

function goToMyPage() {
  router.push({ name: 'MyPage' })
}

function goToTitle() {
  router.push({ name: 'Title' })
}
</script>

<style scoped>
.result-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  padding-bottom: 2rem;
}

/* Header */
.result-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: white;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  position: sticky;
  top: 0;
  z-index: 10;
}

.back-button {
  width: 2.5rem;
  height: 2.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: #f1f5f9;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all 0.2s;
}

.back-button:hover {
  background: #e2e8f0;
}

.back-button svg {
  width: 1.25rem;
  height: 1.25rem;
  color: #475569;
}

.page-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e293b;
}

/* Loading */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
  gap: 1rem;
  color: #64748b;
}

.loading-spinner {
  width: 2.5rem;
  height: 2.5rem;
  border: 3px solid #e2e8f0;
  border-top-color: #738ba8;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Error */
.error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
  gap: 1rem;
  color: #64748b;
}

.retry-btn {
  padding: 0.5rem 1rem;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  border-radius: 0.375rem;
  color: #475569;
  cursor: pointer;
}

/* Result Content */
.result-content {
  padding: 1rem;
  max-width: 40rem;
  margin: 0 auto;
}

/* Score Card */
.score-card {
  background: white;
  border-radius: 1rem;
  padding: 1.5rem;
  margin-bottom: 1rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  text-align: center;
}

.score-circle {
  width: 6rem;
  height: 6rem;
  border-radius: 50%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
  border: 4px solid;
}

.score-circle.high {
  border-color: #22c55e;
  background: #f0fdf4;
}

.score-circle.medium {
  border-color: #f59e0b;
  background: #fffbeb;
}

.score-circle.low {
  border-color: #ef4444;
  background: #fef2f2;
}

.score-value {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1;
}

.score-divider {
  font-size: 1rem;
  color: #94a3b8;
}

.score-total {
  font-size: 1rem;
  color: #64748b;
}

.score-info {
  margin-bottom: 1rem;
}

.score-percentage {
  font-size: 2rem;
  font-weight: 700;
}

.score-percentage.high {
  color: #22c55e;
}

.score-percentage.medium {
  color: #f59e0b;
}

.score-percentage.low {
  color: #ef4444;
}

.score-label {
  font-size: 0.875rem;
  color: #64748b;
}

.session-meta {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
}

.difficulty-badge,
.date-badge {
  font-size: 0.75rem;
  font-weight: 500;
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
}

.difficulty-badge.standard {
  background: #e0e7ef;
  color: #5b7a9f;
}

.difficulty-badge.hard {
  background: #f5efe8;
  color: #b08968;
}

.date-badge {
  background: #f1f5f9;
  color: #64748b;
}

/* Answers Section */
.answers-section {
  margin-bottom: 1.5rem;
}

.section-title {
  font-size: 0.875rem;
  font-weight: 600;
  color: #475569;
  margin-bottom: 0.75rem;
}

.answers-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.answer-item {
  background: white;
  border-radius: 0.75rem;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.answer-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #e2e8f0;
}

.answer-item.correct .answer-header {
  background: #f0fdf4;
}

.answer-item.incorrect .answer-header {
  background: #fef2f2;
}

.question-number {
  font-size: 0.875rem;
  font-weight: 600;
  color: #475569;
}

.answer-status {
  width: 1.5rem;
  height: 1.5rem;
}

.answer-status svg {
  width: 100%;
  height: 100%;
}

.answer-item.correct .answer-status svg {
  color: #22c55e;
}

.answer-item.incorrect .answer-status svg {
  color: #ef4444;
}

.answer-content {
  padding: 1rem;
}

.vocabulary-info {
  margin-bottom: 0.75rem;
}

.word {
  font-size: 1rem;
  font-weight: 600;
  color: #1e293b;
  margin-right: 0.5rem;
}

.meaning {
  font-size: 0.8125rem;
  color: #64748b;
}

.question-text {
  font-size: 0.875rem;
  color: #334155;
  margin-bottom: 0.25rem;
  line-height: 1.5;
}

.question-translation {
  font-size: 0.75rem;
  color: #64748b;
  margin-bottom: 0.75rem;
}

.choices-list {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
  margin-bottom: 0.75rem;
}

.choice-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  border-radius: 0.375rem;
  background: #f8fafc;
  font-size: 0.8125rem;
}

.choice-item.correct-choice {
  background: #dcfce7;
  border: 1px solid #22c55e;
}

.choice-item.wrong-choice {
  background: #fee2e2;
  border: 1px solid #ef4444;
}

.choice-marker {
  font-weight: 600;
  color: #64748b;
  min-width: 1rem;
}

.choice-text {
  flex: 1;
  color: #334155;
}

.choice-badge {
  font-size: 0.625rem;
  font-weight: 600;
  padding: 0.125rem 0.375rem;
  border-radius: 0.25rem;
}

.choice-badge.correct {
  background: #22c55e;
  color: white;
}

.choice-badge.wrong {
  background: #ef4444;
  color: white;
}

.explanation {
  background: #f8fafc;
  border-radius: 0.375rem;
  padding: 0.75rem;
}

.explanation-label {
  font-size: 0.6875rem;
  font-weight: 600;
  color: #94a3b8;
  margin-bottom: 0.25rem;
}

.explanation-text {
  font-size: 0.8125rem;
  color: #475569;
  line-height: 1.5;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 0.75rem;
}

.action-btn {
  flex: 1;
  padding: 0.875rem;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.action-btn.primary {
  background: linear-gradient(135deg, #738ba8 0%, #5b7a9f 100%);
  color: white;
  border: none;
}

.action-btn.primary:hover {
  background: linear-gradient(135deg, #5b7a9f 0%, #4a6785 100%);
}

.action-btn.secondary {
  background: white;
  color: #475569;
  border: 1px solid #e2e8f0;
}

.action-btn.secondary:hover {
  background: #f8fafc;
}
</style>
