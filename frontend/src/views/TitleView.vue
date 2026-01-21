<!-- src/views/TitleView.vue -->
<template>
  <div class="min-h-screen flex flex-col items-center justify-center gap-6 px-4 -my-8">

    <!-- User Info / Auth Button -->
    <div class="user-section">
      <template v-if="auth.isAuthenticated">
        <div class="user-info" @click="goToMyPage">
          <img
            v-if="auth.user?.avatar"
            :src="auth.user.avatar"
            :alt="auth.user.name"
            class="user-avatar"
          />
          <div v-else class="user-avatar-placeholder">
            {{ auth.user?.name?.charAt(0)?.toUpperCase() || 'U' }}
          </div>
          <span class="user-name">{{ auth.user?.name }}</span>
        </div>
        <button class="logout-button" @click="handleLogout">ログアウト</button>
      </template>
      <template v-else>
        <button class="login-button" @click="goToAuth">ログイン / 登録</button>
      </template>
    </div>

    <!-- Title -->
    <div class="text-center">
      <h1 class="title-heading text-3xl md:text-4xl lg:text-5xl font-bold mb-2">
        <span class="title-main">Daily</span>
        <span class="title-sub">Vocabit</span>
      </h1>
      <p class="subtitle">毎日9:00に新しい問題がランダムに生成されます。継続的な学習で着実にスキルアップを目指そう</p>

      <!-- Countdown Timer -->
      <div class="countdown-container">
        <svg class="countdown-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/>
          <path d="M12 6v6l4 2"/>
        </svg>
        <span class="countdown-text">問題の更新まで残り {{ timeUntilUpdate }}</span>
      </div>
    </div>

    <!-- Difficulty Selection Cards -->
    <section class="w-full max-w-md space-y-3">
      <h2 class="text-center text-xs md:text-sm font-semibold text-slate-700 mb-2">難易度を選択</h2>

      <!-- Standard Mode Card -->
      <div
        class="mode-card mode-card-blue"
        :class="{ selected: difficulty === 1 }"
        @click="selectDifficulty(1)"
      >
        <div class="flex items-start gap-3">
          <div class="icon-badge icon-badge-blue">
            <svg class="badge-svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/>
              <circle cx="12" cy="12" r="6"/>
              <circle cx="12" cy="12" r="2"/>
            </svg>
          </div>
          <div class="flex-1">
            <h3 class="text-base md:text-lg font-bold text-slate-800 mb-1">スタンダード</h3>
            <p class="text-xs md:text-sm text-slate-600">
              全10問。基礎的なビジネス英語の語彙・イディオム問題。
            </p>
          </div>
        </div>
      </div>

      <!-- Hard Mode Card -->
      <div
        class="mode-card mode-card-orange"
        :class="{ selected: difficulty === 2 }"
        @click="selectDifficulty(2)"
      >
        <div class="flex items-start gap-3">
          <div class="icon-badge icon-badge-orange">
            <svg class="badge-svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
            </svg>
          </div>
          <div class="flex-1">
            <h3 class="text-base md:text-lg font-bold text-slate-800 mb-1">ハード</h3>
            <p class="text-xs md:text-sm text-slate-600">
              全10問。高度な語彙・イディオムと複雑な構文に挑戦。
            </p>
          </div>
        </div>
      </div>

    </section>

    <!-- Start Button -->
    <button
      class="start-button"
      :disabled="!difficulty || isLoading"
      @click="startQuiz"
    >
      {{ isLoading ? '準備中...' : '学習を始める' }}
    </button>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useQuizStore } from '@/stores/quiz'
import { useAuthStore } from '@/stores/auth'
import { quizSessionApi } from '@/api/client'

const router = useRouter()
const quizStore = useQuizStore()
const auth = useAuthStore()

const difficulty = ref(quizStore.difficulty || null)
const isLoading = ref(false)
const currentTime = ref(new Date())

// 次の更新時刻までの残り時間を計算
const timeUntilUpdate = computed(() => {
  const now = currentTime.value
  const nextUpdate = new Date(now)

  // 日本時間の9:00に設定
  nextUpdate.setHours(9, 0, 0, 0)

  // 現在時刻が9:00を過ぎている場合は翌日の9:00
  if (now.getHours() >= 9) {
    nextUpdate.setDate(nextUpdate.getDate() + 1)
  }

  const diff = nextUpdate - now
  const hours = Math.floor(diff / (1000 * 60 * 60))
  const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))

  return `${hours}h${minutes}m`
})

let intervalId = null

onMounted(() => {
  // 1分ごとに時刻を更新
  intervalId = setInterval(() => {
    currentTime.value = new Date()
  }, 60000)
})

onUnmounted(() => {
  if (intervalId) {
    clearInterval(intervalId)
  }
})

function selectDifficulty(selectedDifficulty) {
  difficulty.value = selectedDifficulty
}

async function startQuiz() {
  if (!difficulty.value) return
  isLoading.value = true

  // modeは'vocab'として固定（単語とイディオムの組み合わせ）
  quizStore.setModeAndDifficulty('vocab', difficulty.value)
  try {
    await quizStore.loadDailyQuestions()

    // ログイン済みの場合、セッションを開始
    if (auth.isAuthenticated) {
      try {
        const response = await quizSessionApi.start(difficulty.value)
        if (response.success && response.data?.sessionId) {
          quizStore.sessionId = response.data.sessionId
        }
      } catch (err) {
        console.error('Failed to start quiz session:', err)
        // セッション開始に失敗してもクイズは続行
      }
    }

    router.push({ name: 'Quiz' })
  } catch (e) {
    console.error(e)
  } finally {
    isLoading.value = false
  }
}

function goToAuth() {
  router.push({ name: 'Auth' })
}

function goToMyPage() {
  router.push({ name: 'MyPage' })
}

async function handleLogout() {
  await auth.logout()
}
</script>

<style scoped>
/* Title Styling */
.title-heading {
  font-family: 'M PLUS Rounded 1c', 'Hiragino Maru Gothic ProN', 'ヒラギノ丸ゴ ProN W4', sans-serif;
  font-weight: 800;
  letter-spacing: 0.05em;
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
  margin-left: 0.5rem;
}

.subtitle {
  color: #64748b;
  font-size: 0.75rem;
  padding: 0 1rem;
}

@media (min-width: 768px) {
  .subtitle {
    font-size: 0.875rem;
  }
}

/* Countdown Container */
.countdown-container {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  margin-top: 1rem;
}

.countdown-icon {
  width: 0.875rem;
  height: 0.875rem;
  color: #94a3b8;
  flex-shrink: 0;
}

.countdown-text {
  font-size: 0.75rem;
  font-weight: 500;
  color: #64748b;
}

@media (min-width: 768px) {
  .countdown-icon {
    width: 1rem;
    height: 1rem;
  }

  .countdown-text {
    font-size: 0.8125rem;
  }
}

/* Icon Wrapper */
.icon-wrapper {
  width: 3.5rem;
  height: 3.5rem;
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  border-radius: 1.25rem;
  box-shadow: 0 8px 16px rgba(59, 130, 246, 0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  animation: float 3s ease-in-out infinite;
}

@keyframes float {
  0%,
  100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-8px);
  }
}

/* Mode Cards */
.mode-card {
  background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%);
  border-radius: 1rem;
  padding: 1.25rem;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  border: 2px solid #e2e8f0;
  border-top: 2px solid #e2e8f0;
  position: relative;
  overflow: hidden;
  min-height: 5.5rem;
}

.mode-card:not(.disabled):hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
  border-color: #cbd5e1;
}

.mode-card:not(.disabled):active {
  transform: translateY(-2px);
}

/* Blue mode styling */
.mode-card-blue.selected {
  border-color: #738ba8;
  border-top-color: #738ba8;
  background: linear-gradient(135deg, #f0f4f8 0%, #e5ecf2 100%);
  box-shadow: 0 8px 24px rgba(115, 139, 168, 0.15);
}

/* Orange mode styling */
.mode-card-orange.selected {
  border-color: #c9a68a;
  border-top-color: #c9a68a;
  background: linear-gradient(135deg, #faf7f4 0%, #f5efe8 100%);
  box-shadow: 0 8px 24px rgba(201, 166, 138, 0.15);
}

/* Icon Badge */
.icon-badge {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 0.625rem;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.2s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.mode-card:not(.disabled):hover .icon-badge {
  transform: scale(1.15) rotate(5deg);
}

.icon-badge-blue {
  background: linear-gradient(135deg, #a8bfd4 0%, #8ca1bc 100%);
}

.icon-badge-orange {
  background: linear-gradient(135deg, #d9c3ae 0%, #c9a68a 100%);
}

.badge-svg-icon {
  width: 1.25rem;
  height: 1.25rem;
  color: white;
}

@media (min-width: 768px) {
  .badge-svg-icon {
    width: 1.5rem;
    height: 1.5rem;
  }
}

/* Start Button */
.start-button {
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
  position: relative;
  overflow: hidden;
}

.start-button::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
  transition: left 0.5s ease;
}

.start-button:hover:not(:disabled)::before {
  left: 100%;
}

.start-button:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 24px rgba(115, 139, 168, 0.35);
  background: linear-gradient(135deg, #5b7a9f 0%, #4a6785 100%);
}

.start-button:active:not(:disabled) {
  transform: translateY(0);
}

.start-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
}

/* Mobile responsive */
@media (min-width: 768px) {
  .mode-card {
    padding: 1.5rem;
  }

  .icon-badge {
    width: 3rem;
    height: 3rem;
  }

  .start-button {
    font-size: 0.9375rem;
    padding: 0.875rem 2.5rem;
  }
}

/* User Section */
.user-section {
  position: absolute;
  top: 1rem;
  right: 1rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  transition: opacity 0.2s ease;
}

.user-info:hover {
  opacity: 0.8;
}

.user-avatar {
  width: 2rem;
  height: 2rem;
  border-radius: 50%;
  object-fit: cover;
}

.user-avatar-placeholder {
  width: 2rem;
  height: 2rem;
  border-radius: 50%;
  background: linear-gradient(135deg, #738ba8 0%, #5b7a9f 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
  font-weight: 600;
}

.user-name {
  font-size: 0.8125rem;
  font-weight: 500;
  color: #475569;
  max-width: 8rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.login-button,
.logout-button {
  padding: 0.375rem 0.875rem;
  font-size: 0.75rem;
  font-weight: 500;
  border-radius: 0.375rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.login-button {
  background: linear-gradient(135deg, #738ba8 0%, #5b7a9f 100%);
  color: white;
  border: none;
}

.login-button:hover {
  background: linear-gradient(135deg, #5b7a9f 0%, #4a6785 100%);
}

.logout-button {
  background: transparent;
  color: #64748b;
  border: 1px solid #e2e8f0;
}

.logout-button:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}

@media (max-width: 480px) {
  .user-name {
    display: none;
  }
}
</style>
