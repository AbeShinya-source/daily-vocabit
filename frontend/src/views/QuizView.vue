<!-- src/views/QuizView.vue -->
<template>
  <div class="min-h-screen bg-background -mx-4 -my-8">
    <!-- Header with Logo -->
    <header class="bg-background/80 backdrop-blur-sm sticky top-0 z-10">
      <div class="mx-auto max-w-2xl px-4 py-4 flex items-center justify-center">
        <button
          @click="confirmExit"
          class="logo-link"
          title="ホームに戻る"
        >
          <h1 class="title-heading">
            <span class="title-main">Daily</span>
            <span class="title-sub">Vocabit</span>
          </h1>
        </button>
      </div>
    </header>

    <div class="mx-auto max-w-2xl px-4 py-6 md:py-10">
      <!-- Loading State -->
      <div v-if="!quiz.isLoaded" class="flex min-h-[60vh] items-center justify-center">
        <div class="text-center">
          <div class="loading-spinner mb-4"></div>
          <p class="text-muted-foreground">問題を読み込んでいます...</p>
        </div>
      </div>

      <!-- Finished State -->
      <div v-else-if="quiz.isFinished" class="flex min-h-[60vh] items-center justify-center">
        <div class="animate-in zoom-in-50 duration-500 text-center space-y-6">
          <div class="text-6xl mb-4">🎉</div>
          <h2 class="text-3xl font-bold text-foreground">お疲れ様でした！</h2>
          <p class="text-lg text-muted-foreground">すべての問題が終了しました</p>
          <button
            class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-primary to-primary/80 px-6 py-3 text-sm font-medium text-primary-foreground shadow-lg transition-all hover:shadow-xl hover:-translate-y-0.5 active:translate-y-0"
            @click="goResult"
          >
            結果を見る
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Quiz Content -->
      <div v-else class="space-y-6">
        <!-- Keyboard Shortcut Guide -->
        <div class="shortcut-guide">
          <span v-if="!isAnswered">
            <kbd>A</kbd><kbd>B</kbd><kbd>C</kbd><kbd>D</kbd> で回答
          </span>
          <span v-else>
            <kbd>Enter</kbd> で次へ
          </span>
        </div>

        <!-- Quiz Header -->
        <div class="space-y-4">
          <!-- Difficulty Display -->
          <div class="flex items-center justify-end">
            <span class="difficulty-label">{{ difficultyLabel }}</span>
          </div>

          <!-- Progress -->
          <div class="space-y-2">
            <div class="flex items-center justify-between text-sm">
              <span class="font-medium text-foreground">
                問題 {{ quiz.currentIndex + 1 }} / {{ quiz.totalQuestions }}
              </span>
              <span class="text-muted-foreground">
                {{ Math.round(((quiz.currentIndex + 1) / quiz.totalQuestions) * 100) }}%
              </span>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full bg-secondary">
              <div
                class="h-full bg-gradient-to-r from-primary to-primary/80 transition-all duration-500 ease-out"
                :style="{ width: `${((quiz.currentIndex + 1) / quiz.totalQuestions) * 100}%` }"
              ></div>
            </div>
          </div>
        </div>

        <!-- Question Card -->
        <div class="animate-in slide-in-from-bottom-4 duration-500 space-y-6 rounded-2xl border border-border bg-card p-6 shadow-lg md:p-8">
          <div class="space-y-4">
            <div class="flex items-start justify-between gap-4">
              <h2 class="text-lg font-semibold leading-relaxed text-card-foreground md:text-xl">
                {{ quiz.currentQuestion.questionText }}
              </h2>
            </div>
          </div>

          <!-- Choices -->
          <div class="space-y-3">
            <button
              v-for="(choice, idx) in quiz.currentQuestion.choices"
              :key="idx"
              :class="[
                'group relative flex w-full items-center gap-4 rounded-xl border-2 p-4 text-left transition-all duration-200',
                choiceButtonClass(idx)
              ]"
              :disabled="isAnswered"
              @click="selectAnswer(idx)"
            >
              <div
                :class="[
                  'flex h-8 w-8 shrink-0 items-center justify-center rounded-lg font-semibold transition-colors',
                  choiceLabelClass(idx)
                ]"
              >
                {{ String.fromCharCode(65 + idx) }}
              </div>
              <span :class="['flex-1 font-medium transition-colors', choiceTextClass(idx)]">
                {{ choice }}
              </span>

              <!-- Correct Icon -->
              <svg
                v-if="isAnswered && idx === feedback.correctIndex"
                class="h-5 w-5 shrink-0 text-success"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                stroke-width="3"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>

              <!-- Incorrect Icon -->
              <svg
                v-else-if="isAnswered && idx === selectedIndex && !feedback.isCorrect"
                class="h-5 w-5 shrink-0 text-destructive"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                stroke-width="3"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Feedback Overlay -->
        <transition
          enter-active-class="animate-in slide-in-from-bottom-4 duration-300"
          leave-active-class="animate-out slide-out-to-bottom-4 duration-200"
        >
          <div
            v-if="isAnswered"
            class="space-y-4 rounded-2xl border border-border bg-card p-6 shadow-lg"
          >
            <div class="flex items-start gap-4">
              <div
                :class="[
                  'flex h-10 w-10 shrink-0 items-center justify-center rounded-lg',
                  feedback.isCorrect ? 'bg-success/10 text-success' : 'bg-destructive/10 text-destructive'
                ]"
              >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
              </div>
              <div class="flex-1 space-y-3">
                <h3 :class="['text-lg font-bold', feedback.isCorrect ? 'text-success' : 'text-destructive']">
                  {{ feedback.isCorrect ? '正解です！' : '不正解です' }}
                </h3>
                <p v-if="quiz.currentQuestion.questionTranslation" class="question-translation">
                  {{ quiz.currentQuestion.questionTranslation }}
                </p>
                <p class="explanation-text text-sm text-muted-foreground">
                  {{ quiz.currentQuestion.explanation }}
                </p>
              </div>
            </div>

            <!-- Next Button -->
            <div class="flex justify-end pt-2">
              <button
                class="group inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-primary to-primary/80 px-6 py-2.5 text-sm font-medium text-primary-foreground shadow-md transition-all hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0"
                @click="handleNext"
              >
                {{ quiz.currentIndex + 1 === quiz.totalQuestions ? '結果を見る' : '次の問題へ' }}
                <svg
                  class="h-4 w-4 transition-transform group-hover:translate-x-1"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  stroke-width="2"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
              </button>
            </div>
          </div>
        </transition>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useQuizStore } from '@/stores/quiz'

const router = useRouter()
const quiz = useQuizStore()

const selectedIndex = ref(null)
const feedback = ref(null) // { isCorrect, correctIndex } or null

const isAnswered = computed(() => feedback.value !== null)

// キーボードショートカット
function handleKeydown(event) {
  // ローディング中または終了後は無視
  if (!quiz.isLoaded || quiz.isFinished) return

  const key = event.key.toLowerCase()

  // 回答前: A/B/C/D または 1/2/3/4 で選択肢を選ぶ
  if (!isAnswered.value) {
    const keyMap = { a: 0, b: 1, c: 2, d: 3, '1': 0, '2': 1, '3': 2, '4': 3 }
    if (key in keyMap) {
      event.preventDefault()
      selectAnswer(keyMap[key])
    }
  }

  // 回答後: Enter または Space で次の問題へ
  if (isAnswered.value && (key === 'enter' || key === ' ')) {
    event.preventDefault()
    handleNext()
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
})

const modeLabel = computed(() => {
  if (quiz.mode === 'vocab') return '単語モード'
  if (quiz.mode === 'idiom') return 'イディオムモード'
  return ''
})

const difficultyLabel = computed(() => {
  if (quiz.difficulty === 1) return 'Standard'
  if (quiz.difficulty === 2) return 'Hard'
  return ''
})

async function selectAnswer(idx) {
  if (isAnswered.value) return

  const result = await quiz.answerCurrentQuestion(idx)
  feedback.value = result
  selectedIndex.value = idx
}

function choiceButtonClass(idx) {
  if (!isAnswered.value) {
    return 'border-border bg-card text-card-foreground hover:border-primary/50 hover:bg-primary/5 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0'
  }

  const isCorrect = idx === feedback.value.correctIndex
  const isSelected = idx === selectedIndex.value

  if (isCorrect) {
    return 'border-success bg-success/10 shadow-md'
  }
  if (isSelected && !isCorrect) {
    return 'border-destructive bg-destructive/10 shadow-md'
  }
  return 'border-border/50 bg-card/50 opacity-40'
}

function choiceLabelClass(idx) {
  if (!isAnswered.value) {
    return 'bg-muted text-muted-foreground group-hover:bg-primary group-hover:text-primary-foreground'
  }

  const isCorrect = idx === feedback.value.correctIndex
  const isSelected = idx === selectedIndex.value

  if (isCorrect) {
    return 'bg-success text-success-foreground'
  }
  if (isSelected && !isCorrect) {
    return 'bg-destructive text-destructive-foreground'
  }
  return 'bg-muted/50 text-muted-foreground'
}

function choiceTextClass(idx) {
  if (!isAnswered.value) {
    return 'text-card-foreground'
  }

  const isCorrect = idx === feedback.value.correctIndex
  const isSelected = idx === selectedIndex.value

  if (isCorrect || (isSelected && !isCorrect)) {
    return 'font-semibold'
  }
  return 'text-muted-foreground'
}

function handleNext() {
  if (quiz.currentIndex + 1 === quiz.totalQuestions) {
    router.push({ name: 'Result' })
    return
  }
  quiz.goToNextQuestion()
  selectedIndex.value = null
  feedback.value = null
}

function goResult() {
  router.push({ name: 'Result' })
}

function goHome() {
  router.push({ name: 'Title' })
}

function confirmExit() {
  if (confirm('クイズを中断してホームに戻りますか？進行状況は保存されません。')) {
    quiz.resetQuiz()
    router.push({ name: 'Title' })
  }
}
</script>

<style scoped>
/* Logo Header */
.logo-link {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
  transition: opacity 0.2s ease;
}

.logo-link:hover {
  opacity: 0.8;
}

.title-heading {
  font-family: 'M PLUS Rounded 1c', 'Hiragino Maru Gothic ProN', 'ヒラギノ丸ゴ ProN W4', sans-serif;
  font-weight: 800;
  letter-spacing: 0.05em;
  font-size: 1.5rem;
}

@media (min-width: 768px) {
  .title-heading {
    font-size: 1.75rem;
  }
}

.title-main {
  background: linear-gradient(135deg, #5b7a9f 0%, #738ba8 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.title-sub {
  background: linear-gradient(135deg, #b08968 0%, #c9a68a 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  font-weight: 700;
  margin-left: 0.3rem;
}

/* Loading Spinner */
.loading-spinner {
  width: 3rem;
  height: 3rem;
  border: 4px solid hsl(var(--primary) / 0.1);
  border-top-color: hsl(var(--primary));
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Animations */
@keyframes animate-in {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes zoom-in-50 {
  from {
    transform: scale(0.5);
  }
  to {
    transform: scale(1);
  }
}

@keyframes slide-in-from-bottom-4 {
  from {
    transform: translateY(1rem);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

@keyframes slide-out-to-bottom-4 {
  from {
    transform: translateY(0);
    opacity: 1;
  }
  to {
    transform: translateY(1rem);
    opacity: 0;
  }
}

.animate-in {
  animation: animate-in 0.5s ease-out;
}

.zoom-in-50 {
  animation: zoom-in-50 0.5s ease-out;
}

.slide-in-from-bottom-4 {
  animation: slide-in-from-bottom-4 0.5s ease-out;
}

.duration-300 {
  animation-duration: 0.3s;
}

.duration-500 {
  animation-duration: 0.5s;
}

.animate-out {
  animation-direction: reverse;
}

.slide-out-to-bottom-4 {
  animation: slide-in-from-bottom-4 0.2s ease-in reverse;
}

.duration-200 {
  animation-duration: 0.2s;
}

/* Keyboard Shortcut Guide */
.shortcut-guide {
  text-align: center;
  font-size: 0.75rem;
  color: hsl(var(--muted-foreground));
  opacity: 0.7;
}

.shortcut-guide kbd {
  display: inline-block;
  padding: 0.125rem 0.375rem;
  margin: 0 0.125rem;
  font-size: 0.6875rem;
  font-family: ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Monaco, Consolas, monospace;
  color: hsl(var(--foreground));
  background: hsl(var(--muted));
  border: 1px solid hsl(var(--border));
  border-radius: 0.25rem;
  box-shadow: 0 1px 0 hsl(var(--border));
}

/* Question translation styling */
.question-translation {
  font-weight: 600;
  color: hsl(var(--foreground));
  font-size: 0.875rem;
  line-height: 1.6;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid hsl(var(--border));
  margin-bottom: 0.5rem;
}

/* Explanation text styling */
.explanation-text {
  white-space: pre-line;
  line-height: 1.8;
  word-break: break-word;
}

/* Difficulty Label */
.difficulty-label {
  font-size: 0.6875rem;
  font-weight: 500;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

@media (min-width: 768px) {
  .difficulty-label {
    font-size: 0.75rem;
  }
}
</style>
