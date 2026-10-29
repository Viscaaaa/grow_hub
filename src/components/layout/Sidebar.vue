<template>
  <aside class="sidebar" :class="{ 'sidebar-open': isOpen, 'sidebar-hidden': isHidden }">
    <div class="sidebar-inner">
      <!-- Main Nav Section -->
      <div class="nav-section">
        <span class="nav-section-title">Main Menu</span>
        <nav class="nav-list">
          <router-link to="/" class="nav-item" @click="$emit('close')">
            <House :size="19" />
            <span>Home</span>
          </router-link>

          <router-link to="/feed" class="nav-item" @click="$emit('close')">
            <BookOpen :size="19" />
            <span>Knowledge Feed</span>
          </router-link>

          <router-link to="/dashboard" class="nav-item" @click="$emit('close')">
            <LayoutDashboard :size="19" />
            <span>My Dashboard</span>
          </router-link>

          <router-link to="/learning" class="nav-item" @click="$emit('close')">
            <GraduationCap :size="19" />
            <span>Learning Center</span>
          </router-link>

          <router-link to="/communities" class="nav-item" @click="$emit('close')">
            <Users :size="19" />
            <span>Communities</span>
          </router-link>

          <router-link to="/saved" class="nav-item" @click="$emit('close')">
            <Bookmark :size="19" />
            <span>Saved</span>
          </router-link>

          <router-link to="/leaderboard" class="nav-item" @click="$emit('close')">
            <Trophy :size="19" />
            <span>Leaderboard</span>
          </router-link>
        </nav>
      </div>

      <!-- My Workspace Section -->
      <div class="nav-section">
        <span class="nav-section-title">My Workspace</span>
        <nav class="nav-list">
          <router-link to="/contributions" class="nav-item" @click="$emit('close')">
            <SquarePen :size="19" />
            <span>My Contributions</span>
          </router-link>
        </nav>
      </div>

      <!-- Administration Section -->
      <div v-if="authStore.isReviewer" class="nav-section">
        <span class="nav-section-title">Administration</span>
        <nav class="nav-list">
          <router-link to="/review" class="nav-item" @click="$emit('close')">
            <ShieldCheck :size="19" />
            <span>Content Validation</span>
          </router-link>

          <router-link v-if="authStore.isAdmin" to="/admin/users" class="nav-item" @click="$emit('close')">
            <Users :size="19" />
            <span>User Management</span>
          </router-link>

          <router-link v-if="authStore.isAdmin" to="/admin/point-rules" class="nav-item" @click="$emit('close')">
            <Sparkles :size="19" />
            <span>Point Settings</span>
          </router-link>
        </nav>
      </div>

      <!-- Other Section -->
      <div class="nav-section">
        <span class="nav-section-title">Support & Profile</span>
        <nav class="nav-list">
          <router-link to="/notifications" class="nav-item" @click="$emit('close')">
            <Bell :size="19" />
            <span>Notifications</span>
          </router-link>

          <router-link to="/help" class="nav-item" @click="$emit('close')">
            <CircleAlert :size="19" />
            <span>Help & Support</span>
          </router-link>

          <router-link to="/profile" class="nav-item" @click="$emit('close')">
            <User :size="19" />
            <span>Profile</span>
          </router-link>
        </nav>
      </div>

      <!-- Mini Gamification Card at bottom -->
      <div v-if="authStore.isAuthenticated" class="sidebar-gamify-card">
        <div class="row-between mb-1">
          <div class="row" style="gap: 6px;">
            <Award :size="16" class="text-green-light" />
            <span class="bold small text-white">Level {{ authStore.userLevel.level }}</span>
          </div>
          <span class="small bold text-green-light">{{ authStore.userLevel.name }}</span>
        </div>

        <div class="progress progress-dark" style="height: 6px; margin: 8px 0 6px;">
          <span :style="{ width: `${authStore.userLevel.progress}%` }"></span>
        </div>

        <div class="row-between small text-white-muted">
          <span>{{ authStore.userLevel.progress }}%</span>
          <span v-if="authStore.userLevel.points_to_next > 0">
            {{ authStore.userLevel.points_to_next }} Pts to Lv.{{ authStore.userLevel.next_level }}
          </span>
          <span v-else>Max Level 🌟</span>
        </div>
      </div>

    </div>

    <!-- Bookmark Tab attached to the right edge (Penanda Halaman Buku) -->
    <button
      type="button"
      class="sidebar-bookmark-tab"
      @click="toggleSidebar"
      :title="isHidden ? 'Show sidebar (Bookmark)' : 'Hide sidebar (Bookmark)'"
      :aria-label="isHidden ? 'Show sidebar (Bookmark)' : 'Hide sidebar (Bookmark)'"
    >
      <div class="bookmark-tab-inner">
        <Bookmark :size="15" class="bookmark-icon" />
        <span class="bookmark-arrow">
          <ChevronLeft v-if="!isHidden" :size="15" />
          <ChevronRight v-else :size="15" />
        </span>
      </div>
    </button>
  </aside>
</template>

<script setup>
import {
  House, BookOpen, LayoutDashboard, GraduationCap, Users,
  SquarePen, Bookmark, Trophy, ShieldCheck, Bell, CircleAlert, User, Award,
  ChevronLeft, ChevronRight, Sparkles
} from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  isOpen: Boolean,
  isHidden: Boolean,
})

const emit = defineEmits(['close', 'hide', 'show', 'toggle'])

const authStore = useAuthStore()

const toggleSidebar = () => {
  if (props.isHidden) {
    emit('show')
  } else {
    emit('hide')
  }
}
</script>

<style scoped src="@/styles/components/sidebar.css"></style>
