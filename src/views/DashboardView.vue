<template>
  <div class="page dashboard-page">
    <div class="page-header">
      <div>
        <h1 class="page-title">My Dashboard</h1>
        <p class="page-subtitle">Track your points progress, level achievements, daily streak, and gamification badges.</p>
      </div>

      <router-link to="/create" class="btn btn-primary">
        <Plus :size="18" />
        <span>Share Content (+5 Pts)</span>
      </router-link>
    </div>

    <div v-if="loading" class="stack">
      <div class="skeleton" style="height: 180px;"></div>
      <div class="skeleton" style="height: 240px;"></div>
    </div>

    <div v-else class="stack">
      <!-- Top Gamification Showcase Cards -->
      <div class="grid grid-3 gamify-summary-grid">
        <!-- Level & Progression Card -->
        <div class="card card-pad card-dark level-card">
          <div class="row-between mb-2">
            <span class="badge badge-light-blur">Skill Level</span>
            <span class="bold small">Rank #{{ data.rank }} Global</span>
          </div>

          <div class="level-name-row my-2">
            <h2 class="level-big-title">Level {{ authStore.userLevel.level }}</h2>
            <div class="level-subtitle">{{ authStore.userLevel.name }}</div>
          </div>

          <div class="progress mt-3">
            <span :style="{ width: `${authStore.userLevel.progress}%` }"></span>
          </div>

          <div class="row-between small opacity-90 mt-2">
            <span>{{ formatNumber(authStore.userPoints) }} Pts</span>
            <span v-if="authStore.userLevel.points_to_next > 0">
              {{ authStore.userLevel.points_to_next }} Pts to Lv.{{ authStore.userLevel.next_level }}
            </span>
            <span v-else>Maximum Level Reached! 🌟</span>
          </div>
        </div>

        <!-- Daily Streak Card -->
        <div class="card card-pad streak-card">
          <div class="row-between mb-2">
            <span class="bold small text-2">Daily Consistency</span>
            <span class="badge badge-amber">+2 Pts / active day</span>
          </div>

          <div class="streak-center">
            <div class="streak-flame-circle">
              <Flame :size="32" class="text-orange flicker" />
            </div>
            <div class="streak-value">{{ authStore.userStreak }} <span class="streak-unit">Days</span></div>
            <div class="streak-desc small muted">Consecutive Active Streak</div>
          </div>

          <div class="row-between small muted pt-2" style="border-top: 1px solid var(--border);">
            <span>Longest Streak:</span>
            <span class="bold text-dark">{{ data.user?.longest_streak || authStore.userStreak }} Days 🔥</span>
          </div>
        </div>

        <!-- Point Stats Summary -->
        <div class="card card-pad points-breakdown-card">
          <span class="bold small text-2 mb-2">Points Earned</span>

          <div class="point-stat-item">
            <span class="muted small">Today</span>
            <span class="bold text-primary">+{{ data.user?.points_today || 0 }} Pts</span>
          </div>

          <div class="point-stat-item">
            <span class="muted small">This Week</span>
            <span class="bold text-dark">+{{ data.points_this_week || 0 }} Pts</span>
          </div>

          <div class="point-stat-item">
            <span class="muted small">This Month</span>
            <span class="bold text-dark">+{{ data.points_this_month || 0 }} Pts</span>
          </div>

          <div class="point-stat-item total-item">
            <span class="bold text-dark">Total Accumulated</span>
            <span class="bold text-primary" style="font-size: 18px;">{{ formatNumber(authStore.userPoints) }} Pts</span>
          </div>
        </div>
      </div>

      <!-- Point 30-Day Activity Trend Bar Chart -->
      <div class="card card-pad mt-4">
        <div class="row-between mb-3">
          <div>
            <h2 class="card-title">Points Trend (Last 30 Days)</h2>
            <p class="small text-2">Your daily knowledge sharing activity and engagement</p>
          </div>
          <span class="badge badge-gray small">30 Days</span>
        </div>

        <div class="chart-container">
          <div
            v-for="(day, idx) in data.chart"
            :key="idx"
            class="chart-bar-wrapper"
            :title="`${day.date}: ${day.points} Pts`"
          >
            <div class="chart-bar-track">
              <div
                class="chart-bar-fill"
                :style="{ height: `${Math.min(100, Math.max(8, (day.points / maxChartPoint) * 100))}%` }"
                :class="{ 'has-points': day.points > 0 }"
              ></div>
            </div>
            <span class="chart-label" v-if="idx % 5 === 0">{{ formatDay(day.date) }}</span>
          </div>
        </div>
      </div>

      <!-- Badges & Achievements Section -->
      <div class="card card-pad mt-4">
        <div class="row-between mb-3">
          <div>
            <h2 class="card-title">Badges & Achievements</h2>
            <p class="small text-2">Unlock achievement badges by actively contributing to the retail community</p>
          </div>
          <span class="badge badge-primary">{{ unlockedBadgesCount }}/{{ data.badges?.length || 8 }} Unlocked</span>
        </div>

        <div class="grid grid-4 badges-grid">
          <div
            v-for="b in data.badges"
            :key="b.key"
            class="badge-card"
            :class="{ 'badge-unlocked': b.earned }"
          >
            <div class="badge-icon-box">
              <component :is="getIcon(b.icon)" :size="24" />
            </div>
            <div class="badge-title bold small">{{ b.name }}</div>
            <p class="badge-desc small muted">{{ b.desc }}</p>

            <div class="badge-progress-wrap mt-2">
              <div class="progress" style="height: 5px;">
                <span :style="{ width: `${(b.progress / b.target) * 100}%` }"></span>
              </div>
              <div class="row-between small muted mt-1" style="font-size: 11px;">
                <span>{{ b.progress }}/{{ b.target }}</span>
                <span>{{ b.earned ? 'Unlocked 🏆' : 'In Progress' }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Activity Heatmap & Recent Ledger Split -->
      <div class="dashboard-split-grid mt-4">
        <!-- Point Ledger (Recent Transactions) -->
        <div class="card card-pad">
          <div class="row-between mb-3">
            <h2 class="card-title">Recent Points Ledger</h2>
            <span class="small muted">Last 12 Activities</span>
          </div>

          <div class="ledger-list stack">
            <div
              v-for="tx in data.recent_points"
              :key="tx.id"
              class="ledger-item"
            >
              <div class="ledger-icon" :class="tx.points > 0 ? 'bg-green-soft' : 'bg-gray'">
                <Plus v-if="tx.points > 0" :size="16" class="text-primary" />
                <Sparkles v-else :size="16" />
              </div>
              <div class="ledger-info truncate">
                <span class="bold small truncate text-dark">{{ tx.description }}</span>
                <span class="small muted">{{ timeAgo(tx.created_at) }}</span>
              </div>
              <div class="ledger-pts bold text-primary">
                +{{ tx.points }} Pts
              </div>
            </div>
          </div>
        </div>

        <!-- Breakdown by Action -->
        <div class="card card-pad">
          <h2 class="card-title mb-3">Your Point Sources</h2>
          <div class="breakdown-list stack">
            <div
              v-for="(total, action) in data.breakdown"
              :key="action"
              class="breakdown-row"
            >
              <span class="bold small text-dark capitalize">{{ formatAction(action) }}</span>
              <div class="row" style="gap: 8px;">
                <span class="small bold text-primary">+{{ total }} Pts</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  Plus, Sparkles, Flame, Sprout, BookOpen, Heart,
  Zap, MessageCircle, GraduationCap, Crown
} from 'lucide-vue-next'
import api from '@/api/client'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const loading = ref(true)
const data = ref({})

const iconMap = {
  Sprout, BookOpen, Heart, Flame, Zap, MessageCircle, GraduationCap, Crown, Sparkles,
}
const getIcon = (name) => iconMap[name] || Sparkles

const fetchDashboard = async () => {
  loading.value = true
  try {
    const res = await api.get('/dashboard')
    data.value = res.data
  } catch (err) {
    console.error('Failed to load dashboard', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchDashboard()
})

const maxChartPoint = computed(() => {
  if (!data.value.chart?.length) return 20
  const max = Math.max(...data.value.chart.map((c) => c.points))
  return Math.max(max, 15)
})

const unlockedBadgesCount = computed(() => {
  return data.value.badges?.filter((b) => b.earned).length || 0
})

const formatNumber = (num) => {
  if (!num) return '0'
  return new Intl.NumberFormat('en-US').format(num)
}

const formatDay = (dateStr) => {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('en-US', { day: 'numeric', month: 'short' })
}

const formatAction = (act) => {
  const map = {
    daily_login: 'Daily Activity (Streak)',
    content_submitted: 'Content Submitted',
    content_published: 'Content Published',
    like_received: 'Likes Received',
    comment_given: 'Comments Posted',
    comment_received: 'Comments Received',
    share_given: 'Content Shared',
    share_received: 'Content Shared by Peers',
    save_received: 'Content Bookmarked',
    course_completed: 'Learning Center Module',
    review_done: 'Reviewer Reward',
  }
  return map[act] || act.replace(/_/g, ' ')
}

const timeAgo = (dateStr) => {
  if (!dateStr) return ''
  const diff = (Date.now() - new Date(dateStr).getTime()) / 1000
  if (diff < 60) return 'Just now'
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`
  if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`
  return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}
</script>

<style scoped src="@/styles/features/dashboard.css"></style>
