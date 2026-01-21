<!-- src/views/BadgesView.vue -->
<template>
  <div class="badges-container">
    <!-- Header -->
    <header class="badges-header">
      <button class="back-button" @click="goBack">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
      </button>
      <h1 class="page-title">実績</h1>
    </header>

    <!-- Loading State -->
    <div v-if="isLoading" class="loading-container">
      <div class="loading-spinner"></div>
      <p>読み込み中...</p>
    </div>

    <!-- Content -->
    <div v-else class="badges-content">
      <!-- Month Navigation -->
      <div class="month-nav">
        <button class="nav-btn" @click="prevMonth">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M15 18l-6-6 6-6"/>
          </svg>
        </button>
        <span class="month-title">{{ year }}年{{ month }}月</span>
        <button class="nav-btn" @click="nextMonth" :disabled="isCurrentMonth">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 18l6-6-6-6"/>
          </svg>
        </button>
      </div>

      <!-- Monthly Stats -->
      <div class="monthly-stats">
        <div class="stat-item">
          <span class="stat-value">{{ stats.days }}</span>
          <span class="stat-label">学習日数</span>
        </div>
        <div class="stat-item">
          <span class="stat-value">{{ stats.sessions }}</span>
          <span class="stat-label">セッション</span>
        </div>
        <div class="stat-item">
          <span class="stat-value">{{ stats.accuracy }}%</span>
          <span class="stat-label">正答率</span>
        </div>
        <div class="stat-item">
          <span class="stat-value">{{ summary.earned }}/{{ summary.total }}</span>
          <span class="stat-label">獲得</span>
        </div>
      </div>

      <!-- Badge Categories -->
      <div class="categories-list">
        <div v-for="category in categories" :key="category.category" class="category-card">
          <div class="category-header">
            <span class="category-name">{{ category.categoryLabel }}</span>
            <span class="category-value">{{ category.currentValue }}{{ getUnit(category.category) }}</span>
          </div>
          <div class="badges-grid">
            <div
              v-for="badge in category.badges"
              :key="badge.id"
              class="badge-card"
              :class="[badge.tier, { earned: badge.isEarned }]"
            >
              <div class="badge-medal" :class="badge.tier">
                <component :is="getBadgeIcon(badge.icon)" />
              </div>
              <div class="badge-details">
                <div class="badge-name">{{ badge.name }}</div>
                <div class="badge-desc">{{ badge.description }}</div>
                <div v-if="!badge.isEarned" class="badge-progress">
                  <div class="progress-bar">
                    <div class="progress-fill" :style="{ width: badge.progress + '%' }"></div>
                  </div>
                  <span class="progress-text">{{ category.currentValue }}/{{ badge.threshold }}</span>
                </div>
                <div v-else class="badge-earned-date">
                  {{ formatEarnedDate(badge.earnedAt) }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Past Badges -->
      <section v-if="history.length > 0" class="history-section">
        <h3 class="section-title">過去の実績</h3>
        <div class="history-list">
          <div v-for="month in history" :key="`${month.year}-${month.month}`" class="history-item">
            <div class="history-month">{{ month.year }}年{{ month.month }}月</div>
            <div class="history-badges">
              <div
                v-for="badge in month.badges"
                :key="badge.id"
                class="history-badge"
                :class="badge.tier"
              >
                <component :is="getBadgeIcon(badge.icon)" />
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, h } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { badgeApi } from '@/api/client'

const router = useRouter()
const auth = useAuthStore()

const isLoading = ref(true)
const year = ref(new Date().getFullYear())
const month = ref(new Date().getMonth() + 1)
const categories = ref([])
const stats = ref({ days: 0, sessions: 0, accuracy: 0 })
const summary = ref({ earned: 0, total: 0 })
const history = ref([])

const isCurrentMonth = computed(() => {
  const now = new Date()
  return year.value === now.getFullYear() && month.value === now.getMonth() + 1
})

onMounted(async () => {
  if (!auth.isAuthenticated) {
    router.push({ name: 'Auth' })
    return
  }
  await loadBadges()
  await loadHistory()
  isLoading.value = false
})

async function loadBadges() {
  try {
    const res = await badgeApi.getMonthly(year.value, month.value)
    if (res.success) {
      categories.value = res.data.categories
      stats.value = res.data.stats
      summary.value = res.data.summary
    }
  } catch (error) {
    console.error('Failed to load badges:', error)
  }
}

async function loadHistory() {
  try {
    const res = await badgeApi.getHistory()
    if (res.success) {
      // 現在の月を除外
      history.value = res.data.history.filter(
        h => !(h.year === year.value && h.month === month.value)
      )
    }
  } catch (error) {
    console.error('Failed to load history:', error)
  }
}

function prevMonth() {
  if (month.value === 1) {
    month.value = 12
    year.value--
  } else {
    month.value--
  }
  loadBadges()
}

function nextMonth() {
  if (isCurrentMonth.value) return
  if (month.value === 12) {
    month.value = 1
    year.value++
  } else {
    month.value++
  }
  loadBadges()
}

function getUnit(category) {
  if (category === 'monthly_accuracy') return '%'
  if (category === 'monthly_days') return '日'
  if (category === 'monthly_streak') return '日'
  return '回'
}

function formatEarnedDate(isoString) {
  if (!isoString) return ''
  const date = new Date(isoString)
  return `${date.getMonth() + 1}/${date.getDate()} 達成`
}

function goBack() {
  router.push({ name: 'MyPage' })
}

// Badge icons
const badgeIcons = {
  calendar: () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2' }, [
    h('rect', { x: '3', y: '4', width: '18', height: '18', rx: '2', ry: '2' }),
    h('line', { x1: '16', y1: '2', x2: '16', y2: '6' }),
    h('line', { x1: '8', y1: '2', x2: '8', y2: '6' }),
    h('line', { x1: '3', y1: '10', x2: '21', y2: '10' }),
  ]),
  book: () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2' }, [
    h('path', { d: 'M4 19.5A2.5 2.5 0 0 1 6.5 17H20' }),
    h('path', { d: 'M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z' }),
  ]),
  target: () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2' }, [
    h('circle', { cx: '12', cy: '12', r: '10' }),
    h('circle', { cx: '12', cy: '12', r: '6' }),
    h('circle', { cx: '12', cy: '12', r: '2' }),
  ]),
  star: () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2' }, [
    h('polygon', { points: '12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2' }),
  ]),
  bolt: () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2' }, [
    h('polygon', { points: '13 2 3 14 12 14 11 22 21 10 12 10 13 2' }),
  ]),
  flame: () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2' }, [
    h('path', { d: 'M12 2c.5 3.5 4 6 4 10a4 4 0 1 1-8 0c0-4 3.5-6.5 4-10z' }),
  ]),
}

function getBadgeIcon(iconName) {
  return badgeIcons[iconName] || badgeIcons.star
}
</script>

<style scoped>
.badges-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  padding-bottom: 2rem;
}

/* Header */
.badges-header {
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
  to { transform: rotate(360deg); }
}

/* Content */
.badges-content {
  padding: 1rem;
  max-width: 40rem;
  margin: 0 auto;
}

/* Month Navigation */
.month-nav {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.nav-btn {
  width: 2.5rem;
  height: 2.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: white;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.nav-btn:hover:not(:disabled) {
  background: #f1f5f9;
}

.nav-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.nav-btn svg {
  width: 1.25rem;
  height: 1.25rem;
  color: #475569;
}

.month-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e293b;
  min-width: 8rem;
  text-align: center;
}

/* Monthly Stats */
.monthly-stats {
  display: flex;
  justify-content: space-between;
  background: white;
  border-radius: 0.75rem;
  padding: 1rem;
  margin-bottom: 1rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 1;
}

.stat-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e293b;
}

.stat-label {
  font-size: 0.625rem;
  color: #64748b;
  margin-top: 0.125rem;
}

/* Categories */
.categories-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.category-card {
  background: white;
  border-radius: 0.75rem;
  padding: 1rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.category-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #e2e8f0;
}

.category-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1e293b;
}

.category-value {
  font-size: 0.75rem;
  font-weight: 500;
  color: #64748b;
}

.badges-grid {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.badge-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  border-radius: 0.5rem;
  background: #f8fafc;
  opacity: 0.6;
  transition: all 0.2s;
}

.badge-card.earned {
  opacity: 1;
}

.badge-card.bronze.earned {
  background: linear-gradient(135deg, #fef3e2 0%, #fde4c4 100%);
}

.badge-card.silver.earned {
  background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%);
}

.badge-card.gold.earned {
  background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
}

.badge-medal {
  width: 3.5rem;
  height: 3.5rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background: #e2e8f0;
  position: relative;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.badge-medal::before {
  content: '';
  position: absolute;
  inset: 3px;
  border-radius: 50%;
  background: inherit;
  border: 2px solid rgba(255, 255, 255, 0.3);
}

.badge-medal::after {
  content: '';
  position: absolute;
  top: 6px;
  left: 50%;
  transform: translateX(-50%);
  width: 60%;
  height: 30%;
  background: linear-gradient(180deg, rgba(255,255,255,0.4) 0%, transparent 100%);
  border-radius: 50%;
}

.badge-medal svg {
  width: 1.5rem;
  height: 1.5rem;
  color: #94a3b8;
  position: relative;
  z-index: 1;
}

.badge-medal.bronze {
  background: linear-gradient(145deg, #e8a060 0%, #cd7f32 50%, #a05a20 100%);
  box-shadow:
    0 4px 8px rgba(205, 127, 50, 0.4),
    inset 0 -2px 4px rgba(0, 0, 0, 0.2),
    inset 0 2px 4px rgba(255, 255, 255, 0.2);
}

.badge-medal.silver {
  background: linear-gradient(145deg, #e8e8e8 0%, #c0c0c0 50%, #909090 100%);
  box-shadow:
    0 4px 8px rgba(150, 150, 150, 0.4),
    inset 0 -2px 4px rgba(0, 0, 0, 0.2),
    inset 0 2px 4px rgba(255, 255, 255, 0.3);
}

.badge-medal.gold {
  background: linear-gradient(145deg, #ffe066 0%, #fbbf24 50%, #d97706 100%);
  box-shadow:
    0 4px 8px rgba(251, 191, 36, 0.5),
    inset 0 -2px 4px rgba(0, 0, 0, 0.2),
    inset 0 2px 4px rgba(255, 255, 255, 0.3);
}

.badge-card.earned .badge-medal svg {
  color: white;
  filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.3));
}

.badge-details {
  flex: 1;
  min-width: 0;
}

.badge-name {
  font-size: 0.8125rem;
  font-weight: 600;
  color: #1e293b;
}

.badge-desc {
  font-size: 0.6875rem;
  color: #64748b;
  margin-top: 0.125rem;
}

.badge-progress {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.375rem;
}

.progress-bar {
  flex: 1;
  height: 0.25rem;
  background: #e2e8f0;
  border-radius: 1rem;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: #94a3b8;
  border-radius: 1rem;
  transition: width 0.3s ease;
}

.progress-text {
  font-size: 0.625rem;
  color: #94a3b8;
  white-space: nowrap;
}

.badge-earned-date {
  font-size: 0.625rem;
  color: #22c55e;
  margin-top: 0.25rem;
  font-weight: 500;
}

/* History Section */
.history-section {
  margin-top: 1.5rem;
}

.section-title {
  font-size: 0.875rem;
  font-weight: 600;
  color: #475569;
  margin-bottom: 0.75rem;
}

.history-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.history-item {
  background: white;
  border-radius: 0.5rem;
  padding: 0.75rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.history-month {
  font-size: 0.8125rem;
  font-weight: 500;
  color: #475569;
}

.history-badges {
  display: flex;
  gap: 0.375rem;
}

.history-badge {
  width: 2rem;
  height: 2rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

.history-badge::before {
  content: '';
  position: absolute;
  inset: 2px;
  border-radius: 50%;
  background: inherit;
  border: 1px solid rgba(255, 255, 255, 0.3);
}

.history-badge svg {
  width: 0.875rem;
  height: 0.875rem;
  color: white;
  position: relative;
  z-index: 1;
  filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.2));
}

.history-badge.bronze {
  background: linear-gradient(145deg, #e8a060 0%, #cd7f32 50%, #a05a20 100%);
  box-shadow: 0 2px 4px rgba(205, 127, 50, 0.4);
}

.history-badge.silver {
  background: linear-gradient(145deg, #e8e8e8 0%, #c0c0c0 50%, #909090 100%);
  box-shadow: 0 2px 4px rgba(150, 150, 150, 0.4);
}

.history-badge.gold {
  background: linear-gradient(145deg, #ffe066 0%, #fbbf24 50%, #d97706 100%);
  box-shadow: 0 2px 4px rgba(251, 191, 36, 0.5);
}
</style>
