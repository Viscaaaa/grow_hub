<template>
  <div class="page home-page">
    <!-- Hero Banner -->
    <div class="card card-dark hero-card">
      <div class="hero-content">
        <div class="hero-badge">
          <Sparkles :size="15" />
          <span>GrowHub Retail Gamification</span>
        </div>
        <h1 class="hero-title">
          Hello, {{ authStore.user?.name }}! 🌱
        </h1>
        <p class="hero-desc">
          Share ideas, field tips, or standard operating procedures today to help retail colleagues grow while earning points and keeping your streak alive!
        </p>

        <div class="hero-stats-row">
          <div class="hero-stat-box">
            <span class="hero-stat-label">Total Points</span>
            <span class="hero-stat-val">{{ formatNumber(authStore.userPoints) }} Pts</span>
          </div>
          <div class="hero-stat-box">
            <span class="hero-stat-label">Daily Streak</span>
            <span class="hero-stat-val">🔥 {{ authStore.userStreak }} Days</span>
          </div>
          <div class="hero-stat-box">
            <span class="hero-stat-label">Your Rank</span>
            <span class="hero-stat-val">#{{ homeData.my_rank || '-' }}</span>
          </div>
        </div>

        <div class="hero-actions">
          <router-link to="/create" class="btn btn-primary btn-lg shadow-green">
            <Plus :size="18" />
            <span>Share Knowledge & Ideas</span>
          </router-link>
          <router-link to="/feed" class="btn btn-outline btn-lg text-white" style="background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.3); color: #fff;">
            <Compass :size="18" />
            <span>Explore Feed</span>
          </router-link>
        </div>
      </div>
    </div>

    <!-- Company Stats Counter -->
    <!-- <div class="grid grid-4 stats-grid mt-4">
      <div class="card card-pad stat">
        <div class="stat-icon bg-green-50">
          <BookOpen :size="22" class="text-primary" />
        </div>
        <div>
          <div class="stat-value">{{ homeData.stats?.published_contents || 0 }}</div>
          <div class="stat-label">Published Contents</div>
        </div>
      </div>

      <div class="card card-pad stat">
        <div class="stat-icon bg-amber-50">
          <Award :size="22" class="text-amber" />
        </div>
        <div>
          <div class="stat-value">{{ homeData.stats?.contributors || 0 }}</div>
          <div class="stat-label">Active Contributors</div>
        </div>
      </div>

      <div class="card card-pad stat">
        <div class="stat-icon bg-blue-50">
          <Users :size="22" class="text-blue" />
        </div>
        <div>
          <div class="stat-value">{{ homeData.stats?.employees || 0 }}</div>
          <div class="stat-label">Registered Employees</div>
        </div>
      </div>

      <div class="card card-pad stat">
        <div class="stat-icon bg-green-50">
          <TrendingUp :size="22" class="text-primary" />
        </div>
        <div>
          <div class="stat-value">{{ homeData.stats?.contents_this_week || 0 }}</div>
          <div class="stat-label">New Ideas This Week</div>
        </div>
      </div>
    </div> -->

    <!-- Categories Grid -->
    <div class="section-container mt-6">
      <div class="row-between mb-3">
        <div>
          <h2 class="card-title">Retail Knowledge Categories</h2>
          <p class="small text-2">Explore topics tailored for store operations and professional growth</p>
        </div>
        <router-link to="/feed" class="btn btn-ghost btn-sm">
          <span>View All</span>
          <ChevronRight :size="16" />
        </router-link>
      </div>

      <div class="categories-grid">
        <router-link
          v-for="cat in homeData.categories"
          :key="cat.id"
          :to="`/feed?category=${cat.slug}`"
          class="cat-tile"
        >
          <div class="cat-tile-icon" :style="{ background: `${cat.color}15`, color: cat.color }">
            <component :is="getIcon(cat.icon)" :size="20" />
          </div>
          <div class="cat-tile-info">
            <span class="cat-tile-name">{{ cat.name }}</span>
            <span class="cat-tile-count">{{ cat.contents_count || 0 }} Posts</span>
          </div>
        </router-link>
      </div>
    </div>

    <!-- Main Content split: Trending & Latest Feed (left) vs Leaderboard & Learning (right) -->
    <div class="home-split-layout mt-6">
      <!-- Left Column: Feeds -->
      <div class="home-feed-col">
        <!-- Trending Section -->
        <div class="section-container">
          <div class="row-between mb-3">
            <div class="row" style="gap: 8px;">
              <Flame :size="20" class="text-orange" />
              <h2 class="card-title">Trending & Most Helpful</h2>
            </div>
            <router-link to="/feed?sort=trending" class="btn btn-ghost btn-sm">
              <span>Full Feed</span>
              <ChevronRight :size="16" />
            </router-link>
          </div>

          <div v-if="loading" class="stack">
            <div class="skeleton" style="height: 180px;"></div>
            <div class="skeleton" style="height: 180px;"></div>
          </div>

          <div v-else-if="homeData.trending?.length > 0" class="stack">
            <ContentCard
              v-for="item in homeData.trending"
              :key="item.id"
              :item="item"
            />
          </div>

          <div v-else class="card card-pad empty">
            <div class="empty-icon"><BookOpen :size="28" /></div>
            <h3>No trending content yet</h3>
            <p class="small muted">Be the first to share an inspiring idea!</p>
          </div>
        </div>

        <!-- Latest Section -->
        <div class="section-container mt-6">
          <div class="row-between mb-3">
            <div class="row" style="gap: 8px;">
              <Clock :size="20" class="text-primary" />
              <h2 class="card-title">Recently Published</h2>
            </div>
            <router-link to="/feed?sort=latest" class="btn btn-ghost btn-sm">
              <span>View More</span>
              <ChevronRight :size="16" />
            </router-link>
          </div>

          <div v-if="homeData.latest?.length > 0" class="stack">
            <ContentCard
              v-for="item in homeData.latest"
              :key="item.id"
              :item="item"
            />
          </div>
        </div>
      </div>

      <!-- Right Column: Top Contributors & Learning Micro-courses -->
      <div class="home-side-col">
        <!-- Weekly Leaderboard Widget -->
        <div class="card card-pad leaderboard-widget">
          <div class="row-between mb-3">
            <div class="row" style="gap: 8px;">
              <Trophy :size="18" class="text-amber" />
              <span class="card-title">Top This Week</span>
            </div>
            <router-link to="/leaderboard" class="small bold text-primary">
              All
            </router-link>
          </div>

          <div class="leader-list">
            <div
              v-for="(leader, idx) in homeData.top_weekly"
              :key="leader.id"
              class="leader-row"
            >
              <div class="leader-rank" :class="`rank-${idx + 1}`">
                {{ idx + 1 }}
              </div>
              <router-link :to="`/profile/${leader.id}`" class="avatar" style="width: 36px; height: 36px; font-size: 13px;">
                <img v-if="leader.avatar_url" :src="leader.avatar_url" :alt="leader.name" />
                <span v-else>{{ initials(leader.name) }}</span>
              </router-link>
              <div class="leader-info truncate">
                <router-link :to="`/profile/${leader.id}`" class="bold small truncate text-dark hover-green">
                  {{ leader.name }}
                </router-link>
                <span class="small muted truncate">{{ leader.department || 'Retail' }}</span>
              </div>
              <div class="leader-score">
                <span class="bold small text-primary">+{{ leader.score }}</span>
                <span class="muted" style="font-size: 10px;">Pts</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Learning Center Widget -->
        <div class="card card-pad mt-4">
          <div class="row-between mb-3">
            <div class="row" style="gap: 8px;">
              <GraduationCap :size="18" class="text-primary" />
              <span class="card-title">Recommended Modules</span>
            </div>
            <router-link to="/learning" class="small bold text-primary">
              Open
            </router-link>
          </div>

          <div class="course-list">
            <router-link
              v-for="course in homeData.courses"
              :key="course.id"
              :to="`/learning/${course.slug}`"
              class="course-mini-card"
            >
              <div class="course-mini-badge" :style="{ background: course.cover_color || '#1e8c45' }">
                <BookOpen :size="14" class="text-white" />
              </div>
              <div class="course-mini-info">
                <span class="course-mini-title clamp-2">{{ course.title }}</span>
                <div class="row small muted" style="gap: 6px;">
                  <span>{{ course.duration_minutes }} mins</span>
                  <span>•</span>
                  <span class="text-primary bold">+{{ course.points_reward }} Pts</span>
                </div>
              </div>
            </router-link>
          </div>
        </div>

        <!-- Gamification Level Progression Card -->
        <div class="card card-pad mt-4 bg-subtle">
          <div class="row" style="gap: 10px;">
            <div class="level-badge-circle">
              <Award :size="20" class="text-primary" />
            </div>
            <div>
              <div class="small muted">Current Level</div>
              <div class="bold text-dark">Level {{ authStore.userLevel.level }} – {{ authStore.userLevel.name }}</div>
            </div>
          </div>

          <div class="progress mt-3">
            <span :style="{ width: `${authStore.userLevel.progress}%` }"></span>
          </div>

          <div class="row-between small muted mt-2">
            <span>{{ authStore.userPoints }} Pts total</span>
            <span v-if="authStore.userLevel.points_to_next > 0">
              {{ authStore.userLevel.points_to_next }} Pts to Lv.{{ authStore.userLevel.next_level }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import {
  Sparkles, Plus, Compass, BookOpen, Award, Users, TrendingUp,
  ChevronRight, Flame, Clock, Trophy, GraduationCap, Lightbulb,
  Rocket, FileText, MessagesSquare, Camera, Building, Leaf, Crown
} from 'lucide-vue-next'
import api from '@/api/client'
import { useAuthStore } from '@/stores/auth'
import ContentCard from '@/components/common/ContentCard.vue'

const authStore = useAuthStore()
const loading = ref(true)
const homeData = ref({
  stats: {},
  my_rank: null,
  trending: [],
  latest: [],
  top_weekly: [],
  categories: [],
  courses: [],
})

const iconMap = {
  Award, Lightbulb, BookOpen, Rocket, FileText, Trophy,
  MessagesSquare, Camera, Building, Leaf, Crown, Users,
}

const getIcon = (name) => {
  return iconMap[name] || BookOpen
}

const fetchHome = async () => {
  loading.value = true
  try {
    const res = await api.get('/home')
    homeData.value = res.data
  } catch (err) {
    console.error('Failed to load home data', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchHome()
})

const formatNumber = (num) => {
  if (!num) return '0'
  return new Intl.NumberFormat('en-US').format(num)
}

const initials = (name) => {
  if (!name) return 'GH'
  return name.split(' ').map((n) => n[0]).slice(0, 2).join('').toUpperCase()
}
</script>

<style scoped src="@/styles/features/home.css"></style>
