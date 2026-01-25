<!-- src/views/MyPageView.vue -->
<template>
  <div class="mypage-container">
    <!-- Header -->
    <header class="mypage-header">
      <button class="back-button" @click="goBack">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
      </button>
      <h1 class="page-title">マイページ</h1>
    </header>

    <!-- Loading State -->
    <div v-if="isLoading" class="loading-container">
      <div class="loading-spinner"></div>
      <p>読み込み中...</p>
    </div>

    <!-- Not Authenticated -->
    <div v-else-if="!auth.isAuthenticated" class="not-authenticated">
      <div class="auth-prompt">
        <svg class="auth-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
          <circle cx="12" cy="7" r="4"/>
        </svg>
        <h2>ログインが必要です</h2>
        <p>学習履歴や統計を確認するにはログインしてください</p>
        <button class="login-btn" @click="goToAuth">ログイン / 登録</button>
      </div>
    </div>

    <!-- Dashboard Content -->
    <div v-else class="dashboard-content">
      <!-- User Profile Card -->
      <section class="profile-card">
        <div class="profile-info">
          <img
            v-if="auth.user?.avatar"
            :src="auth.user.avatar"
            :alt="auth.user.name"
            class="profile-avatar"
          />
          <div v-else class="profile-avatar-placeholder">
            {{ auth.user?.name?.charAt(0)?.toUpperCase() || 'U' }}
          </div>
          <div class="profile-details">
            <h2 class="profile-name">{{ auth.user?.name }}</h2>
            <p class="profile-email">{{ auth.user?.email }}</p>
          </div>
        </div>
      </section>

      <!-- Notification Settings -->
      <section class="settings-card">
        <h3 class="section-title">通知設定</h3>
        <div class="setting-item">
          <div class="setting-info">
            <span class="setting-label">メール通知</span>
            <span class="setting-description">毎日9時に新しい問題の通知を受け取る</span>
          </div>
          <label class="toggle-switch">
            <input
              type="checkbox"
              :checked="auth.user?.email_notification_enabled"
              @change="toggleNotification"
              :disabled="isUpdatingNotification"
            />
            <span class="toggle-slider"></span>
          </label>
        </div>
      </section>

      <!-- Stats Overview -->
      <section class="stats-overview">
        <h3 class="section-title">学習統計</h3>
        <div class="stats-grid">
          <!-- Total Questions -->
          <div class="stat-card stat-card-blue">
            <div class="stat-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/>
                <rect x="9" y="3" width="6" height="4" rx="2"/>
                <path d="M9 12h6m-6 4h6"/>
              </svg>
            </div>
            <div class="stat-value">{{ stats.total?.totalQuestions || 0 }}</div>
            <div class="stat-label">総問題数</div>
          </div>

          <!-- Correct Answers -->
          <div class="stat-card stat-card-green">
            <div class="stat-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
              </svg>
            </div>
            <div class="stat-value">{{ stats.total?.correctAnswers || 0 }}</div>
            <div class="stat-label">正解数</div>
          </div>

          <!-- Accuracy -->
          <div class="stat-card stat-card-purple">
            <div class="stat-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <circle cx="12" cy="12" r="6"/>
                <circle cx="12" cy="12" r="2"/>
              </svg>
            </div>
            <div class="stat-value">{{ stats.total?.accuracy || 0 }}%</div>
            <div class="stat-label">正答率</div>
          </div>

          <!-- Streak -->
          <div class="stat-card stat-card-orange">
            <div class="stat-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
              </svg>
            </div>
            <div class="stat-value">{{ stats.streak || 0 }}</div>
            <div class="stat-label">連続学習日数</div>
          </div>
        </div>
      </section>

      <!-- Calendar & Badges Two Column -->
      <div class="two-column-section">
        <!-- Calendar Section -->
        <section class="calendar-section">
          <h3 class="section-title">学習カレンダー</h3>
          <div class="calendar-card">
            <!-- Calendar Navigation -->
            <div class="calendar-nav">
              <button class="nav-btn" @click="prevMonth">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M15 18l-6-6 6-6"/>
                </svg>
              </button>
              <span class="calendar-title">{{ calendarYear }}年{{ calendarMonth }}月</span>
              <button class="nav-btn" @click="nextMonth" :disabled="isCurrentMonth">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M9 18l6-6-6-6"/>
                </svg>
              </button>
            </div>

            <!-- Calendar Grid -->
            <div class="calendar-grid">
              <div class="calendar-weekday" v-for="day in weekdays" :key="day">{{ day }}</div>
              <div
                v-for="(day, index) in calendarDays"
                :key="index"
                class="calendar-day"
                :class="{
                  'other-month': !day.isCurrentMonth,
                  'today': day.isToday,
                  'has-study': day.studied,
                }"
                @mouseenter="showTooltip($event, day)"
                @mouseleave="hideTooltip"
              >
                <span class="day-number">{{ day.day }}</span>
                <div v-if="day.studied && day.isCurrentMonth" class="study-indicators">
                  <span v-if="day.standard" class="indicator standard"></span>
                  <span v-if="day.hard" class="indicator hard"></span>
                </div>
              </div>
            </div>

            <!-- Legend -->
            <div class="calendar-legend">
              <div class="legend-item">
                <span class="indicator standard"></span>
                <span>Standard</span>
              </div>
              <div class="legend-item">
                <span class="indicator hard"></span>
                <span>Hard</span>
              </div>
            </div>
          </div>
        </section>

        <!-- Right Column: Badges + Session History -->
        <div class="right-column">
          <!-- Badges Section -->
          <section class="badges-section">
            <div class="badges-header-row">
              <h3 class="section-title">実績</h3>
              <button class="view-all-btn" @click="goToBadges">すべて見る</button>
            </div>
            <div class="recent-badges-card" @click="goToBadges">
              <div class="recent-badges-summary">
                <span class="summary-count">{{ badgeSummary.totalEarned }}</span>
                <span class="summary-label">獲得済み</span>
              </div>
              <div v-if="recentBadges.length > 0" class="recent-badges-list">
                <div class="recent-badges-label">最近の実績</div>
                <div class="recent-badges-row">
                  <div
                    v-for="badge in recentBadges"
                    :key="`${badge.id}-${badge.year}-${badge.month}`"
                    class="recent-badge-wrapper"
                    @mouseenter.stop="showBadgeTooltip($event, badge)"
                    @mouseleave.stop="hideBadgeTooltip"
                    @click.stop
                  >
                    <div class="recent-badge" :class="badge.tier">
                      <component :is="getBadgeIcon(badge.icon)" />
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="no-badges">
                <span>まだ実績がありません</span>
              </div>
              <div class="badges-arrow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M9 18l6-6-6-6"/>
                </svg>
              </div>
            </div>
            <!-- Badge Tooltip -->
            <div
              v-if="badgeTooltip.visible"
              class="badge-tooltip"
              :style="{ top: badgeTooltip.y + 'px', left: badgeTooltip.x + 'px' }"
            >
              <div class="badge-tooltip-name">{{ badgeTooltip.name }}</div>
              <div class="badge-tooltip-desc">{{ badgeTooltip.description }}</div>
              <div class="badge-tooltip-date">{{ badgeTooltip.month }}月 達成</div>
            </div>
          </section>

          <!-- Session History (in right column for desktop) -->
          <section class="session-section-inline">
            <h3 class="section-title">学習履歴</h3>
            <div class="session-list-scroll">
              <div v-if="sessions.length === 0" class="no-history-inline">
                <p>まだ学習履歴がありません</p>
              </div>
              <div v-else class="session-list-inline">
                <div
                  v-for="session in sessions"
                  :key="session.id"
                  class="session-item-compact"
                  @click="goToResult(session.id)"
                >
                  <div class="session-date-compact">
                    <span class="date-day">{{ formatDay(session.completedAt) }}</span>
                    <span class="date-month">{{ formatMonth(session.completedAt) }}</span>
                  </div>
                  <div class="session-info-compact">
                    <div class="session-difficulty" :class="session.difficulty === 1 ? 'standard' : 'hard'">
                      {{ session.difficultyLabel }}
                    </div>
                    <div class="session-score">
                      <span class="score-correct">{{ session.correctCount }}</span>
                      <span class="score-divider">/</span>
                      <span class="score-total">{{ session.totalQuestions }}</span>
                    </div>
                  </div>
                  <div class="session-accuracy-compact" :class="getAccuracyClass(session.accuracy)">
                    {{ session.accuracy }}%
                  </div>
                </div>
              </div>
            </div>
            <!-- Load More -->
            <div v-if="sessionPagination.currentPage < sessionPagination.lastPage" class="load-more-inline">
              <button class="load-more-btn-small" @click="loadMoreSessions" :disabled="isLoadingMore">
                {{ isLoadingMore ? '...' : 'もっと見る' }}
              </button>
            </div>
          </section>
        </div>
      </div>

      <!-- Tooltip -->
      <div
        v-if="tooltip.visible"
        class="calendar-tooltip"
        :style="{ top: tooltip.y + 'px', left: tooltip.x + 'px' }"
      >
        <div class="tooltip-date">{{ tooltip.date }}</div>
        <div v-if="tooltip.sessions.length === 0" class="tooltip-empty">学習なし</div>
        <div v-else class="tooltip-sessions">
          <div
            v-for="session in tooltip.sessions"
            :key="session.id"
            class="tooltip-session"
          >
            <span class="tooltip-difficulty" :class="session.difficulty === 1 ? 'standard' : 'hard'">
              {{ session.difficultyLabel }}
            </span>
            <span class="tooltip-score">{{ session.correctCount }}/{{ session.totalQuestions }}</span>
            <span class="tooltip-rank" :class="getRankClass(session.accuracy)">{{ getRank(session.accuracy) }}</span>
          </div>
        </div>
      </div>

      <!-- Session History (mobile only) -->
      <section class="session-section-mobile">
        <h3 class="section-title">学習履歴</h3>
        <div v-if="sessions.length === 0" class="no-history">
          <p>まだ学習履歴がありません</p>
        </div>
        <div v-else class="session-list">
          <div
            v-for="session in sessions"
            :key="session.id"
            class="session-item"
            @click="goToResult(session.id)"
          >
            <div class="session-date">
              <span class="date-day">{{ formatDay(session.completedAt) }}</span>
              <span class="date-month">{{ formatMonth(session.completedAt) }}</span>
            </div>
            <div class="session-info">
              <div class="session-difficulty" :class="session.difficulty === 1 ? 'standard' : 'hard'">
                {{ session.difficultyLabel }}
              </div>
              <div class="session-score">
                <span class="score-correct">{{ session.correctCount }}</span>
                <span class="score-divider">/</span>
                <span class="score-total">{{ session.totalQuestions }}</span>
              </div>
            </div>
            <div class="session-accuracy" :class="getAccuracyClass(session.accuracy)">
              {{ session.accuracy }}%
            </div>
            <div class="session-arrow">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 18l6-6-6-6"/>
              </svg>
            </div>
          </div>
        </div>

        <!-- Load More -->
        <div v-if="sessionPagination.currentPage < sessionPagination.lastPage" class="load-more">
          <button class="load-more-btn" @click="loadMoreSessions" :disabled="isLoadingMore">
            {{ isLoadingMore ? '読み込み中...' : 'もっと見る' }}
          </button>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, h } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { statsApi, badgeApi } from '@/api/client'

const router = useRouter()
const auth = useAuthStore()

const isLoading = ref(true)
const isLoadingMore = ref(false)
const isUpdatingNotification = ref(false)
const stats = ref({
  total: null,
  today: null,
  weekly: [],
  streak: 0,
})
const sessions = ref([])
const sessionPagination = ref({
  currentPage: 1,
  lastPage: 1,
  total: 0,
})

// Calendar state
const calendarYear = ref(new Date().getFullYear())
const calendarMonth = ref(new Date().getMonth() + 1)
const calendarData = ref([])
const weekdays = ['日', '月', '火', '水', '木', '金', '土']

// Tooltip state
const tooltip = ref({
  visible: false,
  x: 0,
  y: 0,
  date: '',
  sessions: [],
})

// Badge state
const recentBadges = ref([])
const badgeSummary = ref({ totalEarned: 0 })

// Badge tooltip state
const badgeTooltip = ref({
  visible: false,
  x: 0,
  y: 0,
  name: '',
  description: '',
  month: 0,
})

const isCurrentMonth = computed(() => {
  const now = new Date()
  return calendarYear.value === now.getFullYear() && calendarMonth.value === now.getMonth() + 1
})

const calendarDays = computed(() => {
  const year = calendarYear.value
  const month = calendarMonth.value
  const firstDay = new Date(year, month - 1, 1)
  const lastDay = new Date(year, month, 0)
  const daysInMonth = lastDay.getDate()
  const startWeekday = firstDay.getDay()

  const days = []

  // Previous month days
  const prevMonthLastDay = new Date(year, month - 1, 0).getDate()
  for (let i = startWeekday - 1; i >= 0; i--) {
    days.push({
      day: prevMonthLastDay - i,
      isCurrentMonth: false,
      isToday: false,
      studied: false,
      standard: false,
      hard: false,
      sessions: [],
    })
  }

  // Current month days
  const today = new Date()
  for (let i = 1; i <= daysInMonth; i++) {
    const dateStr = `${year}-${String(month).padStart(2, '0')}-${String(i).padStart(2, '0')}`
    const dayData = calendarData.value.find(d => d.date === dateStr)
    const isToday = today.getFullYear() === year && today.getMonth() + 1 === month && today.getDate() === i

    days.push({
      day: i,
      dateStr,
      isCurrentMonth: true,
      isToday,
      studied: !!dayData,
      standard: dayData?.standard || false,
      hard: dayData?.hard || false,
      sessions: dayData?.sessions || [],
    })
  }

  // Next month days
  const remaining = 42 - days.length
  for (let i = 1; i <= remaining; i++) {
    days.push({
      day: i,
      isCurrentMonth: false,
      isToday: false,
      studied: false,
      standard: false,
      hard: false,
      sessions: [],
    })
  }

  return days
})

onMounted(async () => {
  if (auth.isAuthenticated) {
    await loadDashboard()
  }
  isLoading.value = false
})

async function loadDashboard() {
  try {
    const [dashboardRes, sessionsRes, calendarRes, badgesRes] = await Promise.all([
      statsApi.getDashboard(),
      statsApi.getSessions({ page: 1, per_page: 10 }),
      statsApi.getCalendar(calendarYear.value, calendarMonth.value),
      badgeApi.getRecent(5),
    ])

    if (dashboardRes.success) {
      stats.value = dashboardRes.data
    }

    if (sessionsRes.success) {
      sessions.value = sessionsRes.data.sessions
      sessionPagination.value = sessionsRes.data.pagination
    }

    if (calendarRes.success) {
      calendarData.value = calendarRes.data.days
    }

    if (badgesRes.success) {
      recentBadges.value = badgesRes.data.badges
      badgeSummary.value = badgesRes.data.summary
    }
  } catch (error) {
    console.error('Failed to load dashboard:', error)
  }
}

async function loadMoreSessions() {
  if (isLoadingMore.value) return

  isLoadingMore.value = true
  try {
    const res = await statsApi.getSessions({
      page: sessionPagination.value.currentPage + 1,
      per_page: 10,
    })

    if (res.success) {
      sessions.value = [...sessions.value, ...res.data.sessions]
      sessionPagination.value = res.data.pagination
    }
  } catch (error) {
    console.error('Failed to load more sessions:', error)
  } finally {
    isLoadingMore.value = false
  }
}

async function loadCalendar() {
  try {
    const res = await statsApi.getCalendar(calendarYear.value, calendarMonth.value)
    if (res.success) {
      calendarData.value = res.data.days
    }
  } catch (error) {
    console.error('Failed to load calendar:', error)
  }
}

function prevMonth() {
  if (calendarMonth.value === 1) {
    calendarMonth.value = 12
    calendarYear.value--
  } else {
    calendarMonth.value--
  }
  loadCalendar()
}

function nextMonth() {
  if (isCurrentMonth.value) return

  if (calendarMonth.value === 12) {
    calendarMonth.value = 1
    calendarYear.value++
  } else {
    calendarMonth.value++
  }
  loadCalendar()
}

function showTooltip(event, day) {
  if (!day.isCurrentMonth) return

  const rect = event.target.getBoundingClientRect()
  const containerRect = document.querySelector('.mypage-container').getBoundingClientRect()

  tooltip.value = {
    visible: true,
    x: rect.left - containerRect.left + rect.width / 2,
    y: rect.bottom - containerRect.top + 8,
    date: `${calendarMonth.value}/${day.day}`,
    sessions: day.sessions,
  }
}

function hideTooltip() {
  tooltip.value.visible = false
}

function showBadgeTooltip(event, badge) {
  const rect = event.target.getBoundingClientRect()
  const containerRect = document.querySelector('.mypage-container').getBoundingClientRect()

  badgeTooltip.value = {
    visible: true,
    x: rect.left - containerRect.left + rect.width / 2,
    y: rect.top - containerRect.top - 8,
    name: badge.name,
    description: badge.description,
    month: badge.month,
  }
}

function hideBadgeTooltip() {
  badgeTooltip.value.visible = false
}

function getRank(accuracy) {
  if (accuracy === 100) return 'S'
  if (accuracy >= 90) return 'A'
  if (accuracy >= 80) return 'B'
  if (accuracy >= 70) return 'C'
  if (accuracy >= 60) return 'D'
  return 'E'
}

function getRankClass(accuracy) {
  const rank = getRank(accuracy)
  return `rank-${rank.toLowerCase()}`
}

function formatDay(isoString) {
  return new Date(isoString).getDate()
}

function formatMonth(isoString) {
  const date = new Date(isoString)
  return `${date.getMonth() + 1}月`
}

function getAccuracyClass(accuracy) {
  if (accuracy >= 80) return 'high'
  if (accuracy >= 60) return 'medium'
  return 'low'
}

function goBack() {
  router.push({ name: 'Title' })
}

function goToAuth() {
  router.push({ name: 'Auth' })
}

function goToResult(sessionId) {
  router.push({ name: 'SessionResult', params: { id: sessionId } })
}

function goToBadges() {
  router.push({ name: 'Badges' })
}

async function toggleNotification(event) {
  const enabled = event.target.checked
  isUpdatingNotification.value = true

  try {
    const result = await auth.updateNotificationSettings(enabled)
    if (!result.success) {
      // Revert the checkbox on failure
      event.target.checked = !enabled
    }
  } finally {
    isUpdatingNotification.value = false
  }
}

// Badge icons as render functions
const badgeIcons = {
  flame: () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2' }, [
    h('path', { d: 'M12 2c.5 3.5 4 6 4 10a4 4 0 1 1-8 0c0-4 3.5-6.5 4-10z' }),
  ]),
  book: () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2' }, [
    h('path', { d: 'M4 19.5A2.5 2.5 0 0 1 6.5 17H20' }),
    h('path', { d: 'M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z' }),
  ]),
  check: () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2' }, [
    h('polyline', { points: '20 6 9 17 4 12' }),
  ]),
  star: () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2' }, [
    h('polygon', { points: '12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2' }),
  ]),
  sun: () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2' }, [
    h('circle', { cx: '12', cy: '12', r: '5' }),
    h('line', { x1: '12', y1: '1', x2: '12', y2: '3' }),
    h('line', { x1: '12', y1: '21', x2: '12', y2: '23' }),
    h('line', { x1: '4.22', y1: '4.22', x2: '5.64', y2: '5.64' }),
    h('line', { x1: '18.36', y1: '18.36', x2: '19.78', y2: '19.78' }),
    h('line', { x1: '1', y1: '12', x2: '3', y2: '12' }),
    h('line', { x1: '21', y1: '12', x2: '23', y2: '12' }),
    h('line', { x1: '4.22', y1: '19.78', x2: '5.64', y2: '18.36' }),
    h('line', { x1: '18.36', y1: '5.64', x2: '19.78', y2: '4.22' }),
  ]),
  bolt: () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2' }, [
    h('polygon', { points: '13 2 3 14 12 14 11 22 21 10 12 10 13 2' }),
  ]),
}

function getBadgeIcon(iconName) {
  return badgeIcons[iconName] || badgeIcons.star
}
</script>

<style scoped>
.mypage-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  padding-bottom: 2rem;
  position: relative;
}

/* Header */
.mypage-header {
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

/* Not Authenticated */
.not-authenticated {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
  padding: 2rem;
}

.auth-prompt {
  text-align: center;
  max-width: 20rem;
}

.auth-icon {
  width: 4rem;
  height: 4rem;
  color: #94a3b8;
  margin-bottom: 1rem;
}

.auth-prompt h2 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 0.5rem;
}

.auth-prompt p {
  font-size: 0.875rem;
  color: #64748b;
  margin-bottom: 1.5rem;
}

.login-btn {
  padding: 0.75rem 2rem;
  background: linear-gradient(135deg, #738ba8 0%, #5b7a9f 100%);
  color: white;
  border: none;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.login-btn:hover {
  background: linear-gradient(135deg, #5b7a9f 0%, #4a6785 100%);
}

/* Dashboard Content */
.dashboard-content {
  padding: 1rem;
  max-width: 56rem;
  margin: 0 auto;
}

.section-title {
  font-size: 0.875rem;
  font-weight: 600;
  color: #475569;
  margin-bottom: 0.75rem;
}

/* Profile Card */
.profile-card {
  background: white;
  border-radius: 1rem;
  padding: 1.25rem;
  margin-bottom: 1rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.profile-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.profile-avatar {
  width: 3.5rem;
  height: 3.5rem;
  border-radius: 50%;
  object-fit: cover;
}

.profile-avatar-placeholder {
  width: 3.5rem;
  height: 3.5rem;
  border-radius: 50%;
  background: linear-gradient(135deg, #738ba8 0%, #5b7a9f 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  font-weight: 600;
}

.profile-name {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e293b;
}

.profile-email {
  font-size: 0.8125rem;
  color: #64748b;
  margin-top: 0.25rem;
}

/* Settings Card */
.settings-card {
  background: white;
  border-radius: 1rem;
  padding: 1.25rem;
  margin-bottom: 1rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.setting-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.setting-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.setting-label {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #1e293b;
}

.setting-description {
  font-size: 0.75rem;
  color: #64748b;
}

/* Toggle Switch */
.toggle-switch {
  position: relative;
  display: inline-block;
  width: 3rem;
  height: 1.625rem;
  flex-shrink: 0;
}

.toggle-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.toggle-slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #cbd5e1;
  border-radius: 1rem;
  transition: 0.3s;
}

.toggle-slider::before {
  position: absolute;
  content: "";
  height: 1.25rem;
  width: 1.25rem;
  left: 0.1875rem;
  bottom: 0.1875rem;
  background-color: white;
  border-radius: 50%;
  transition: 0.3s;
}

.toggle-switch input:checked + .toggle-slider {
  background: linear-gradient(135deg, #738ba8 0%, #5b7a9f 100%);
}

.toggle-switch input:checked + .toggle-slider::before {
  transform: translateX(1.375rem);
}

.toggle-switch input:disabled + .toggle-slider {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Stats Overview */
.stats-overview {
  margin-bottom: 1rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem;
}

.stat-card {
  background: white;
  border-radius: 0.75rem;
  padding: 1rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.stat-icon {
  width: 2rem;
  height: 2rem;
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 0.5rem;
}

.stat-icon svg {
  width: 1.125rem;
  height: 1.125rem;
}

.stat-card-blue .stat-icon {
  background: #dbeafe;
}

.stat-card-blue .stat-icon svg {
  color: #3b82f6;
}

.stat-card-green .stat-icon {
  background: #dcfce7;
}

.stat-card-green .stat-icon svg {
  color: #22c55e;
}

.stat-card-purple .stat-icon {
  background: #f3e8ff;
}

.stat-card-purple .stat-icon svg {
  color: #a855f7;
}

.stat-card-orange .stat-icon {
  background: #ffedd5;
}

.stat-card-orange .stat-icon svg {
  color: #f97316;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
}

.stat-label {
  font-size: 0.75rem;
  color: #64748b;
  margin-top: 0.25rem;
}

/* Calendar Section */
.calendar-section {
  margin-bottom: 0;
}

.calendar-card {
  background: white;
  border-radius: 0.75rem;
  padding: 1rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.calendar-nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.nav-btn {
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: #f1f5f9;
  border-radius: 0.375rem;
  cursor: pointer;
  transition: all 0.2s;
}

.nav-btn:hover:not(:disabled) {
  background: #e2e8f0;
}

.nav-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.nav-btn svg {
  width: 1rem;
  height: 1rem;
  color: #475569;
}

.calendar-title {
  font-size: 1rem;
  font-weight: 600;
  color: #1e293b;
}

.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 0.25rem;
}

.calendar-weekday {
  text-align: center;
  font-size: 0.625rem;
  font-weight: 600;
  color: #94a3b8;
  padding: 0.25rem 0;
}

.calendar-day {
  aspect-ratio: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border-radius: 0.375rem;
  font-size: 0.75rem;
  color: #475569;
  position: relative;
  gap: 0.125rem;
  cursor: default;
  transition: all 0.15s;
}

.calendar-day.other-month {
  color: #cbd5e1;
}

.calendar-day.today {
  background: #e0e7ef;
  font-weight: 600;
  color: #1e293b;
}

.calendar-day.has-study {
  background: #f0fdf4;
}

.calendar-day.has-study.today {
  background: linear-gradient(135deg, #e0e7ef 0%, #dcfce7 100%);
}

.calendar-day:not(.other-month):hover {
  background: #f1f5f9;
}

.calendar-day.has-study:not(.other-month):hover {
  background: #dcfce7;
}

.day-number {
  line-height: 1;
}

.study-indicators {
  display: flex;
  gap: 0.1875rem;
}

.indicator {
  width: 0.4375rem;
  height: 0.4375rem;
  border-radius: 50%;
}

.indicator.standard {
  background: #738ba8;
}

.indicator.hard {
  background: #c9a68a;
}

.calendar-legend {
  display: flex;
  justify-content: center;
  gap: 1rem;
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px solid #e2e8f0;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.6875rem;
  color: #64748b;
}

/* Tooltip */
.calendar-tooltip {
  position: absolute;
  z-index: 100;
  background: white;
  border-radius: 0.5rem;
  padding: 0.625rem;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
  min-width: 8rem;
  transform: translateX(-50%);
  pointer-events: none;
}

.calendar-tooltip::before {
  content: '';
  position: absolute;
  top: -6px;
  left: 50%;
  transform: translateX(-50%);
  border-left: 6px solid transparent;
  border-right: 6px solid transparent;
  border-bottom: 6px solid white;
}

.tooltip-date {
  font-size: 0.75rem;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 0.375rem;
  text-align: center;
}

.tooltip-empty {
  font-size: 0.6875rem;
  color: #94a3b8;
  text-align: center;
}

.tooltip-sessions {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.tooltip-session {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.6875rem;
}

.tooltip-difficulty {
  padding: 0.125rem 0.375rem;
  border-radius: 0.25rem;
  font-weight: 600;
  font-size: 0.625rem;
  min-width: 3.25rem;
  text-align: center;
}

.tooltip-difficulty.standard {
  background: #e0e7ef;
  color: #5b7a9f;
}

.tooltip-difficulty.hard {
  background: #f5efe8;
  color: #b08968;
}

.tooltip-score {
  color: #475569;
  flex: 1;
}

.tooltip-rank {
  font-weight: 700;
  font-size: 0.75rem;
  min-width: 1rem;
  text-align: center;
}

.tooltip-rank.rank-s {
  color: #f59e0b;
}

.tooltip-rank.rank-a {
  color: #22c55e;
}

.tooltip-rank.rank-b {
  color: #3b82f6;
}

.tooltip-rank.rank-c {
  color: #8b5cf6;
}

.tooltip-rank.rank-d {
  color: #64748b;
}

.tooltip-rank.rank-e {
  color: #ef4444;
}

/* Two Column Section */
.two-column-section {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
  margin-bottom: 1rem;
}

@media (min-width: 640px) {
  .two-column-section {
    grid-template-columns: 6fr 4fr;
    align-items: start;
  }
}

/* Badges Section */
.badges-section {
  margin-bottom: 0;
  flex-shrink: 0;
}

.badges-header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.badges-header-row .section-title {
  margin-bottom: 0;
}

.view-all-btn {
  font-size: 0.75rem;
  color: #738ba8;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  transition: all 0.2s;
}

.view-all-btn:hover {
  background: #f1f5f9;
  color: #5b7a9f;
}

.recent-badges-card {
  background: white;
  border-radius: 0.75rem;
  padding: 1rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  display: flex;
  align-items: center;
  gap: 1rem;
  cursor: pointer;
  transition: all 0.2s;
}

.recent-badges-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.recent-badges-summary {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 4rem;
  padding-right: 1rem;
  border-right: 1px solid #e2e8f0;
}

.summary-count {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1;
}

.summary-label {
  font-size: 0.625rem;
  color: #64748b;
  margin-top: 0.25rem;
}

.recent-badges-list {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.recent-badges-label {
  font-size: 0.625rem;
  color: #94a3b8;
}

.recent-badges-row {
  display: flex;
  gap: 0.5rem;
}

.recent-badge {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

.recent-badge::before {
  content: '';
  position: absolute;
  inset: 2px;
  border-radius: 50%;
  background: inherit;
  border: 1px solid rgba(255, 255, 255, 0.3);
}

.recent-badge::after {
  content: '';
  position: absolute;
  top: 4px;
  left: 50%;
  transform: translateX(-50%);
  width: 60%;
  height: 30%;
  background: linear-gradient(180deg, rgba(255,255,255,0.35) 0%, transparent 100%);
  border-radius: 50%;
}

.recent-badge svg {
  width: 1.125rem;
  height: 1.125rem;
  color: white;
  position: relative;
  z-index: 1;
  filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.2));
}

.recent-badge.bronze {
  background: linear-gradient(145deg, #e8a060 0%, #cd7f32 50%, #a05a20 100%);
  box-shadow:
    0 3px 6px rgba(205, 127, 50, 0.4),
    inset 0 -1px 3px rgba(0, 0, 0, 0.2),
    inset 0 1px 3px rgba(255, 255, 255, 0.2);
}

.recent-badge.silver {
  background: linear-gradient(145deg, #e8e8e8 0%, #c0c0c0 50%, #909090 100%);
  box-shadow:
    0 3px 6px rgba(150, 150, 150, 0.4),
    inset 0 -1px 3px rgba(0, 0, 0, 0.2),
    inset 0 1px 3px rgba(255, 255, 255, 0.3);
}

.recent-badge-wrapper {
  position: relative;
  cursor: pointer;
}

.recent-badge.gold {
  background: linear-gradient(145deg, #ffe066 0%, #fbbf24 50%, #d97706 100%);
  box-shadow:
    0 3px 6px rgba(251, 191, 36, 0.5),
    inset 0 -1px 3px rgba(0, 0, 0, 0.2),
    inset 0 1px 3px rgba(255, 255, 255, 0.3);
}

.no-badges {
  flex: 1;
  font-size: 0.8125rem;
  color: #94a3b8;
}

.badges-arrow {
  color: #94a3b8;
  flex-shrink: 0;
}

.badges-arrow svg {
  width: 1.25rem;
  height: 1.25rem;
}

/* Badge Tooltip */
.badge-tooltip {
  position: absolute;
  z-index: 100;
  background: white;
  border-radius: 0.5rem;
  padding: 0.625rem 0.75rem;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
  min-width: 10rem;
  max-width: 14rem;
  transform: translate(-50%, -100%);
  pointer-events: none;
}

.badge-tooltip::after {
  content: '';
  position: absolute;
  bottom: -6px;
  left: 50%;
  transform: translateX(-50%);
  border-left: 6px solid transparent;
  border-right: 6px solid transparent;
  border-top: 6px solid white;
}

.badge-tooltip-name {
  font-size: 0.8125rem;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 0.25rem;
}

.badge-tooltip-desc {
  font-size: 0.6875rem;
  color: #64748b;
  line-height: 1.4;
  margin-bottom: 0.375rem;
}

.badge-tooltip-date {
  font-size: 0.625rem;
  color: #22c55e;
  font-weight: 500;
}

/* Right Column */
.right-column {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

/* Session Section Inline (desktop) */
.session-section-inline {
  display: none;
}

@media (min-width: 640px) {
  .session-section-inline {
    display: block;
  }
}

.session-list-scroll {
  overflow-y: auto;
  max-height: 20rem;
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.session-list-inline {
  display: flex;
  flex-direction: column;
}

.session-item-compact {
  padding: 0.625rem 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  transition: background 0.15s;
  border-bottom: 1px solid #f1f5f9;
}

.session-item-compact:last-child {
  border-bottom: none;
}

.session-item-compact:hover {
  background: #f8fafc;
}

.session-date-compact {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 2rem;
}

.session-date-compact .date-day {
  font-size: 1rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1;
}

.session-date-compact .date-month {
  font-size: 0.5625rem;
  color: #64748b;
}

.session-info-compact {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.session-info-compact .session-difficulty {
  font-size: 0.625rem;
  padding: 0.0625rem 0.375rem;
}

.session-info-compact .session-score {
  font-size: 0.6875rem;
}

.session-accuracy-compact {
  font-size: 0.8125rem;
  font-weight: 700;
  min-width: 2.5rem;
  text-align: right;
}

.session-accuracy-compact.high {
  color: #22c55e;
}

.session-accuracy-compact.medium {
  color: #f59e0b;
}

.session-accuracy-compact.low {
  color: #ef4444;
}

.no-history-inline {
  padding: 1.5rem;
  text-align: center;
  color: #94a3b8;
  font-size: 0.75rem;
}

.load-more-inline {
  padding: 0.5rem;
  text-align: center;
}

.load-more-btn-small {
  padding: 0.375rem 1rem;
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #e2e8f0;
  border-radius: 0.375rem;
  font-size: 0.6875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.load-more-btn-small:hover:not(:disabled) {
  background: #e2e8f0;
}

.load-more-btn-small:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Session Section Mobile */
.session-section-mobile {
  margin-bottom: 1rem;
}

@media (min-width: 640px) {
  .session-section-mobile {
    display: none;
  }
}

/* Session Section */
.session-section {
  margin-bottom: 1rem;
}

.no-history {
  background: white;
  border-radius: 0.75rem;
  padding: 2rem;
  text-align: center;
  color: #64748b;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.session-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.session-item {
  background: white;
  border-radius: 0.75rem;
  padding: 0.875rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  cursor: pointer;
  transition: all 0.2s;
}

.session-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.session-date {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 2.5rem;
}

.date-day {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1;
}

.date-month {
  font-size: 0.625rem;
  color: #64748b;
}

.session-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.session-difficulty {
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.125rem 0.5rem;
  border-radius: 0.25rem;
  display: inline-block;
  width: fit-content;
}

.session-difficulty.standard {
  background: #e0e7ef;
  color: #5b7a9f;
}

.session-difficulty.hard {
  background: #f5efe8;
  color: #b08968;
}

.session-score {
  font-size: 0.8125rem;
  color: #475569;
}

.score-correct {
  font-weight: 600;
  color: #1e293b;
}

.score-divider {
  margin: 0 0.125rem;
  color: #94a3b8;
}

.score-total {
  color: #64748b;
}

.session-accuracy {
  font-size: 1rem;
  font-weight: 700;
  min-width: 3rem;
  text-align: right;
}

.session-accuracy.high {
  color: #22c55e;
}

.session-accuracy.medium {
  color: #f59e0b;
}

.session-accuracy.low {
  color: #ef4444;
}

.session-arrow {
  color: #94a3b8;
}

.session-arrow svg {
  width: 1.25rem;
  height: 1.25rem;
}

/* Load More */
.load-more {
  margin-top: 1rem;
  text-align: center;
}

.load-more-btn {
  padding: 0.625rem 1.5rem;
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  font-size: 0.8125rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.load-more-btn:hover:not(:disabled) {
  background: #e2e8f0;
}

.load-more-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Responsive */
@media (min-width: 640px) {
  .stats-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}
</style>
