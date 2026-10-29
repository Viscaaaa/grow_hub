<template>
  <header class="topbar">
    <div class="topbar-left">
      <button class="mobile-toggle btn btn-ghost btn-icon" @click="$emit('toggle-sidebar')" aria-label="Menu">
        <Menu :size="20" />
      </button>

      <router-link to="/" class="brand-link">
        <div class="brand-logo">
          <Sprout :size="22" stroke-width="2.5" />
        </div>
        <div class="brand-text">
          <span class="brand-name">Grow<span class="brand-sub">Hub</span></span>
          <span class="brand-tagline">Retail Learning Hub</span>
        </div>
      </router-link>
    </div>

    <!-- Center/Right: Gamification stats + Actions + User -->
    <div class="topbar-right">
      <!-- Gamification Indicators -->
      <div v-if="authStore.isAuthenticated" class="gamification-pills">
        <!-- Points Pill -->
        <router-link
          to="/dashboard"
          class="gamify-pill pill-points"
          title="Total points earned"
        >
          <div class="pill-icon-glow bg-amber-soft">
            <Sparkles :size="15" class="text-amber" />
          </div>
          <div class="pill-info">
            <span class="pill-val">{{ formatNumber(authStore.userPoints) }}</span>
            <span class="pill-lbl">Pts</span>
          </div>
        </router-link>

        <!-- Streak Pill -->
        <router-link
          to="/dashboard"
          class="gamify-pill pill-streak"
          :title="`Active streak: ${authStore.userStreak} consecutive days`"
        >
          <div class="pill-icon-glow bg-orange-soft">
            <Flame :size="16" class="text-orange" :class="{ 'flicker': authStore.userStreak > 0 }" />
          </div>
          <div class="pill-info">
            <span class="pill-val">{{ authStore.userStreak }}</span>
            <span class="pill-lbl">Days</span>
          </div>
        </router-link>

        <!-- Level Pill -->
        <router-link
          to="/dashboard"
          class="gamify-pill pill-level"
          :title="`Level ${authStore.userLevel.level}: ${authStore.userLevel.name}`"
        >
          <div class="pill-icon-glow bg-green-soft">
            <Award :size="15" class="text-primary" />
          </div>
          <div class="pill-info">
            <span class="pill-val">Lv.{{ authStore.userLevel.level }}</span>
            <span class="pill-lbl">{{ authStore.userLevel.name }}</span>
          </div>
        </router-link>
      </div>

      <!-- Quick Upload Button -->
      <router-link to="/create" class="btn btn-primary btn-sm upload-quick-btn">
        <Plus :size="16" />
        <span class="hide-mobile">Upload Content</span>
      </router-link>

      <!-- Reviewer Portal Quick Badge (if reviewer or admin) -->
      <router-link
        v-if="authStore.isReviewer"
        to="/review"
        class="btn btn-dark btn-sm hide-mobile"
        title="Employee content review"
      >
        <ShieldCheck :size="16" />
        <span>Reviewer</span>
      </router-link>

      <!-- Notifications -->
      <router-link to="/notifications" class="notif-btn" aria-label="Notifications">
        <Bell :size="19" />
        <span v-if="unreadCount > 0" class="notif-badge">{{ unreadCount > 9 ? '9+' : unreadCount }}</span>
      </router-link>

      <!-- User Dropdown Menu -->
      <div class="user-menu-wrapper" ref="menuRef">
        <button class="user-profile-btn" @click="menuOpen = !menuOpen" aria-label="User Menu">
          <div class="avatar" style="width: 38px; height: 38px; font-size: 13px;">
            <img v-if="authStore.user?.avatar_url" :src="authStore.user.avatar_url" :alt="authStore.user.name" />
            <span v-else>{{ initials(authStore.user?.name) }}</span>
          </div>
          <div class="user-profile-text hide-tablet">
            <span class="user-name truncate">{{ authStore.user?.name }}</span>
            <span class="user-dept truncate small muted">{{ authStore.user?.department || 'Employee' }}</span>
          </div>
          <ChevronDown :size="16" class="muted hide-tablet" />
        </button>

        <!-- Dropdown Popup -->
        <transition name="pop">
          <div v-if="menuOpen" class="user-dropdown">
            <div class="dropdown-header">
              <div class="bold truncate">{{ authStore.user?.name }}</div>
              <div class="small muted">ID: {{ authStore.user?.nip }}</div>
              <span class="badge badge-gray small mt-1" style="width: fit-content;">
                {{ authStore.user?.role?.toUpperCase() }}
              </span>
            </div>

            <div class="dropdown-divider"></div>

            <router-link to="/profile" class="dropdown-item" @click="menuOpen = false">
              <User :size="16" />
              <span>My Profile</span>
            </router-link>

            <router-link to="/dashboard" class="dropdown-item" @click="menuOpen = false">
              <LayoutDashboard :size="16" />
              <span>My Dashboard & Points</span>
            </router-link>

            <router-link to="/contributions" class="dropdown-item" @click="menuOpen = false">
              <BookOpen :size="16" />
              <span>My Contributions</span>
            </router-link>

            <router-link v-if="authStore.isReviewer" to="/review" class="dropdown-item" @click="menuOpen = false">
              <ShieldCheck :size="16" class="text-primary" />
              <span class="bold text-primary">Reviewer Portal</span>
            </router-link>

            <router-link v-if="authStore.isReviewer" to="/admin/users" class="dropdown-item" @click="menuOpen = false">
              <Users :size="16" class="text-primary" />
              <span class="bold text-primary">User Management</span>
            </router-link>

            <div class="dropdown-divider"></div>

            <button class="dropdown-item text-red" @click="handleLogout">
              <LogOut :size="16" />
              <span>Sign Out (Logout)</span>
            </button>
          </div>
        </transition>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import {
  Menu, Sprout, Sparkles, Flame, Award, Plus, ShieldCheck,
  Bell, ChevronDown, User, LayoutDashboard, BookOpen, LogOut, Users
} from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import api from '@/api/client'

defineEmits(['toggle-sidebar'])

const authStore = useAuthStore()
const router = useRouter()
const menuOpen = ref(false)
const menuRef = ref(null)
const unreadCount = ref(0)

const fetchUnread = async () => {
  if (!authStore.isAuthenticated) return
  try {
    const res = await api.get('/notifications/unread-count')
    unreadCount.value = res.data.unread || 0
  } catch (err) {
    // ignore
  }
}

const handleLogout = async () => {
  menuOpen.value = false
  await authStore.logout()
  router.push('/login')
}

const handleClickOutside = (e) => {
  if (menuRef.value && !menuRef.value.contains(e.target)) {
    menuOpen.value = false
  }
}

onMounted(() => {
  fetchUnread()
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})

const formatNumber = (num) => {
  if (!num) return '0'
  return new Intl.NumberFormat('id-ID').format(num)
}

const initials = (name) => {
  if (!name) return 'GH'
  return name
    .split(' ')
    .map((n) => n[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()
}
</script>

<style scoped src="@/styles/components/navbar.css"></style>
