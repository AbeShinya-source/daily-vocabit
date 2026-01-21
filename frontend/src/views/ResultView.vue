<template>
  <div class="min-h-screen flex flex-col items-center justify-center px-4 py-8 -my-8">
    <!-- Result Header -->
    <div class="result-header">
      <h1 class="result-title">Result</h1>

      <!-- Score Card -->
      <div class="score-card">
        <!-- Circular Progress -->
        <div class="circular-progress">
          <svg class="progress-ring" viewBox="0 0 200 200">
            <!-- Background circle -->
            <circle
              class="progress-ring-bg"
              cx="100"
              cy="100"
              r="85"
            />
            <!-- Progress circle -->
            <circle
              class="progress-ring-circle"
              :class="scoreColorClass"
              cx="100"
              cy="100"
              r="85"
              :style="{ strokeDashoffset: progressOffset }"
            />
          </svg>

          <div class="progress-content">
            <div class="score-main">
              <span class="score-number" :class="scoreColorClass">{{ quiz.score }}</span>
              <span class="score-separator">/</span>
              <span class="score-total">{{ quiz.totalQuestions }}</span>
            </div>
            <div class="score-percentage" :class="scoreColorClass">{{ scorePercentage }}%</div>
          </div>
        </div>

        <div class="score-info">
          <div class="score-badge">
            <span class="badge-text">{{ difficultyLabel }}</span>
          </div>
          <div class="score-rank" :class="rankColorClass">
            <span class="rank-label">RANK</span>
            <span class="rank-grade">{{ scoreRank }}</span>
          </div>
        </div>
      </div>

      <!-- Back Button (Top) -->
      <button class="back-button-top" @click="backToTitle">タイトルに戻る</button>
    </div>

    <!-- Questions Review -->
    <div class="w-full max-w-2xl space-y-3 mt-8">
      <h2 class="review-header">問題の復習</h2>

      <div v-for="(q, idx) in quiz.questions" :key="q.id" class="question-review-card">
        <div class="review-question-header">
          <span class="review-question-number">Q{{ idx + 1 }}</span>
          <span v-if="isQuestionCorrect(q)" class="review-status correct">✓ 正解</span>
          <span v-else class="review-status incorrect">✗ 不正解</span>
        </div>

        <p class="review-question-text">{{ q.questionText }}</p>

        <ul class="review-choices">
          <li v-for="(choice, cIdx) in q.choices" :key="cIdx" :class="choiceClass(q, cIdx)">
            <span class="choice-marker">{{ String.fromCharCode(65 + cIdx) }}</span>
            <span>{{ choice }}</span>
          </li>
        </ul>

        <div class="review-explanation">
          <div class="explanation-label">
            <svg class="explanation-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/>
              <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
              <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            <span>解説</span>
          </div>
          <div class="explanation-translation">{{ q.questionTranslation }}</div>
          <p class="explanation-detail">{{ q.explanation }}</p>
        </div>
      </div>
    </div>

    <!-- Back Button -->
    <button class="back-button" @click="backToTitle">タイトルに戻る</button>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useQuizStore } from '@/stores/quiz'
import { useAuthStore } from '@/stores/auth'
import { quizSessionApi } from '@/api/client'

const router = useRouter()
const quiz = useQuizStore()
const auth = useAuthStore()

// 結果画面が表示されたらセッションを完了
onMounted(async () => {
  if (auth.isAuthenticated && quiz.sessionId) {
    try {
      await quizSessionApi.complete(quiz.sessionId, quiz.score)
    } catch (err) {
      console.error('Failed to complete quiz session:', err)
    }
  }
})

const scorePercentage = computed(() => {
  return Math.round((quiz.score / quiz.totalQuestions) * 100)
})

const difficultyLabel = computed(() => {
  return quiz.difficulty === 1 ? 'スタンダード' : 'ハード'
})

const scoreRank = computed(() => {
  const percentage = scorePercentage.value
  if (percentage === 100) return 'S'
  if (percentage >= 90) return 'A'
  if (percentage >= 70) return 'B'
  if (percentage >= 50) return 'C'
  return 'D'
})

const scoreColorClass = computed(() => {
  const percentage = scorePercentage.value
  if (percentage >= 90) return 'score-excellent'
  if (percentage >= 70) return 'score-good'
  if (percentage >= 50) return 'score-average'
  return 'score-poor'
})

const rankColorClass = computed(() => {
  const rank = scoreRank.value
  if (rank === 'S') return 'rank-s'
  if (rank === 'A') return 'rank-a'
  if (rank === 'B') return 'rank-b'
  if (rank === 'C') return 'rank-c'
  return 'rank-d' // 50%未満
})

const progressOffset = computed(() => {
  const radius = 85
  const circumference = 2 * Math.PI * radius
  const progress = scorePercentage.value / 100
  return circumference * (1 - progress)
})

function getAnswerForQuestion(questionId) {
  return quiz.answers.find((a) => a.questionId === questionId) || null
}

function isQuestionCorrect(q) {
  const answer = getAnswerForQuestion(q.id)
  if (!answer) return false
  return answer.selectedIndex === q.correctIndex
}

function choiceClass(q, cIdx) {
  const answer = getAnswerForQuestion(q.id)
  if (!answer) return ''

  const isCorrect = cIdx === q.correctIndex
  const isSelected = cIdx === answer.selectedIndex

  if (isCorrect && isSelected) return 'choice-correct-selected'
  if (isCorrect) return 'choice-correct'
  if (isSelected && !isCorrect) return 'choice-incorrect-selected'
  return 'choice-default'
}

function backToTitle() {
  quiz.resetQuiz()
  router.push({ name: 'Title' })
}
</script>

<style scoped>
/* Result Header */
.result-header {
  text-align: center;
  margin-bottom: 2rem;
}

.result-icon {
  width: 4rem;
  height: 4rem;
  margin: 0 auto 0.5rem;
  color: #738ba8;
}

.result-icon svg {
  width: 100%;
  height: 100%;
}

.result-title {
  font-family: 'M PLUS Rounded 1c', 'Hiragino Maru Gothic ProN', sans-serif;
  font-size: 2.5rem;
  font-weight: 800;
  letter-spacing: 0.05em;
  background: linear-gradient(135deg, #738ba8 0%, #5b7a9f 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 1.5rem;
}

/* Score Card */
.score-card {
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  border-radius: 1.25rem;
  padding: 2rem;
  box-shadow: 0 4px 20px rgba(115, 139, 168, 0.15);
  min-width: 400px;
  animation: scale-in 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes scale-in {
  from {
    transform: scale(0.9);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}

.score-main {
  display: flex;
  align-items: baseline;
  justify-content: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.score-number {
  font-size: 3.5rem;
  font-weight: bold;
  background: linear-gradient(135deg, #738ba8 0%, #5b7a9f 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  animation: number-bounce 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55) 0.2s backwards;
}

@keyframes number-bounce {
  from {
    transform: scale(0);
  }
  to {
    transform: scale(1);
  }
}

.score-separator {
  font-size: 2rem;
  color: #cbd5e1;
}

.score-total {
  font-size: 2rem;
  font-weight: 600;
  color: #94a3b8;
}

.score-percentage {
  font-size: 1.125rem;
  font-weight: 600;
}

/* Circular Progress */
.circular-progress {
  position: relative;
  width: 200px;
  height: 200px;
  margin: 0 auto 1.5rem;
}

.progress-ring {
  width: 100%;
  height: 100%;
  transform: rotate(-90deg);
}

.progress-ring-bg {
  fill: none;
  stroke: #e2e8f0;
  stroke-width: 12;
}

.progress-ring-circle {
  fill: none;
  stroke-width: 12;
  stroke-linecap: round;
  stroke-dasharray: 534.07;
  stroke-dashoffset: 534.07;
  transition: stroke-dashoffset 1s cubic-bezier(0.4, 0, 0.2, 1) 0.3s;
}

.progress-content {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
}

/* Score Color Classes */
.score-excellent {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.progress-ring-circle.score-excellent {
  stroke: url(#gradient-excellent);
  stroke: #10b981;
}

.score-good {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.progress-ring-circle.score-good {
  stroke: #3b82f6;
}

.score-average {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.progress-ring-circle.score-average {
  stroke: #f59e0b;
}

.score-poor {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.progress-ring-circle.score-poor {
  stroke: #ef4444;
}

/* Score Info Section */
.score-info {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.score-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.5rem 1rem;
  background: linear-gradient(135deg, #f0f4f8 0%, #e5ecf2 100%);
  border-radius: 0.5rem;
  font-size: 0.875rem;
  border: 1px solid #dae4ed;
}

.badge-text {
  color: #475569;
  font-weight: 600;
}

/* Score Rank */
.score-rank {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  font-weight: 700;
  border: 2px solid;
}

.rank-label {
  font-size: 0.625rem;
  letter-spacing: 0.05em;
  opacity: 0.8;
}

.rank-grade {
  font-size: 1.5rem;
  font-weight: 800;
  line-height: 1;
}

/* Rank Color Classes */
.rank-s {
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  border-color: #fbbf24;
  color: #d97706;
}

.rank-a {
  background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
  border-color: #10b981;
  color: #059669;
}

.rank-b {
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
  border-color: #3b82f6;
  color: #2563eb;
}

.rank-c {
  background: linear-gradient(135deg, #fed7aa 0%, #fdba74 100%);
  border-color: #f59e0b;
  color: #d97706;
}

.rank-d {
  background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%);
  border-color: #ef4444;
  color: #dc2626;
}

/* Review Section */
.review-header {
  font-size: 1rem;
  font-weight: 600;
  color: #334155;
  margin-bottom: 1rem;
}

.question-review-card {
  background: white;
  border-radius: 1rem;
  padding: 1.25rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.review-question-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.review-question-number {
  display: inline-block;
  padding: 0.25rem 0.625rem;
  background: linear-gradient(135deg, #738ba8 0%, #5b7a9f 100%);
  color: white;
  border-radius: 0.375rem;
  font-size: 0.75rem;
  font-weight: 600;
}

.review-status {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.625rem;
  border-radius: 0.375rem;
  font-size: 0.75rem;
  font-weight: 600;
}

.review-status.correct {
  background: #dcfce7;
  color: #16a34a;
}

.review-status.incorrect {
  background: #fee2e2;
  color: #dc2626;
}

.review-question-text {
  font-size: 0.875rem;
  font-weight: 500;
  color: #1e293b;
  margin-bottom: 1rem;
  line-height: 1.5;
}

.review-choices {
  list-style: none;
  padding: 0;
  margin: 0 0 1rem 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.review-choices li {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 0.75rem;
  border-radius: 0.5rem;
  font-size: 0.8125rem;
}

.choice-marker {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 1.5rem;
  height: 1.5rem;
  border-radius: 0.375rem;
  font-weight: 600;
  font-size: 0.75rem;
  flex-shrink: 0;
}

.choice-default {
  background: #f8fafc;
  color: #64748b;
}

.choice-default .choice-marker {
  background: white;
  color: #94a3b8;
}

.choice-correct {
  background: #f0fdf4;
  color: #16a34a;
}

.choice-correct .choice-marker {
  background: #bbf7d0;
  color: #15803d;
}

.choice-correct-selected {
  background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
  color: #15803d;
  font-weight: 600;
  border: 2px solid #22c55e;
}

.choice-correct-selected .choice-marker {
  background: #22c55e;
  color: white;
}

.choice-incorrect-selected {
  background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
  color: #dc2626;
  font-weight: 600;
  border: 2px solid #ef4444;
}

.choice-incorrect-selected .choice-marker {
  background: #ef4444;
  color: white;
}

/* Explanation */
.review-explanation {
  background: #f8fafc;
  border-left: 3px solid #738ba8;
  padding: 0.875rem;
  border-radius: 0.5rem;
}

.explanation-label {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #738ba8;
  margin-bottom: 0.5rem;
}

.explanation-icon {
  width: 1rem;
  height: 1rem;
  flex-shrink: 0;
}

/* 問題文和訳（解説の最初の部分） */
.explanation-translation {
  font-weight: 700;
  color: #1e293b;
  font-size: 0.875rem;
  line-height: 1.6;
  margin-bottom: 0.75rem;
  word-break: break-word;
}

/* 詳細な解説部分 */
.explanation-detail {
  font-size: 0.8125rem;
  color: #475569;
  line-height: 1.8;
  margin: 0;
  white-space: pre-line;
  word-break: break-word;
}

/* Back Button */
.back-button {
  margin-top: 2rem;
  padding: 0.75rem 2rem;
  background: linear-gradient(135deg, #738ba8 0%, #5b7a9f 100%);
  color: white;
  border: none;
  border-radius: 0.75rem;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 4px 16px rgba(115, 139, 168, 0.25);
}

.back-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 24px rgba(115, 139, 168, 0.35);
  background: linear-gradient(135deg, #5b7a9f 0%, #4a6785 100%);
}

/* Back Button (Top - below score card) */
.back-button-top {
  margin-top: 1.5rem;
  padding: 0.625rem 1.5rem;
  background: transparent;
  color: #738ba8;
  border: 1px solid #738ba8;
  border-radius: 0.5rem;
  font-size: 0.8125rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.back-button-top:hover {
  background: #738ba8;
  color: white;
}

/* Mobile responsive */
@media (min-width: 768px) {
  .result-title {
    font-size: 3rem;
  }

  .score-card {
    padding: 2.5rem;
  }

  .question-review-card {
    padding: 1.5rem;
  }

  .review-question-text {
    font-size: 1rem;
  }

  .review-choices li {
    font-size: 0.875rem;
  }

  .back-button {
    font-size: 0.875rem;
    padding: 0.75rem 1.75rem;
  }
}
</style>
