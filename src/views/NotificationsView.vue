<template>
  <div class="page notifications-page">
    <div class="page-header">
      <div>
        <h1 class="page-title">Notifications</h1>
        <p class="page-subtitle">Activity alerts regarding your content, points appreciation, level ups, and coworker engagements.</p>
      </div>

      <div class="row" style="gap: 8px;">
        <button
          v-if="unreadCount > 0"
          class="btn btn-outline btn-sm"
          @click="markAllAsRead"
        >
          <CheckCheck :size="16" />
          <span>Mark All as Read</span>
        </button>
      </div>
    </div>

    <!-- Filter chips -->
    <div class="chips mb-4">
      <button
        class="chip"
        :class="{ active: filter === 'all' }"
        @click="filter = 'all'; fetchNotifications()"
      >
        All
      </button>
      <button
        class="chip"
        :class="{ active: filter === 'unread' }"
        @click="filter = 'unread'; fetchNotifications()"
      >
        Unread ({{ unreadCount }})
      </button>
    </div>

    <!-- Notifications List -->
    <div v-if="loading" class="stack">
      <div class="skeleton" style="height: 72px;"></div>
      <div class="skeleton" style="height: 72px;"></div>
    </div>

    <div v-else-if="items.length > 0" class="stack notifications-list">
      <div
        v-for="item in items"
        :key="item.id"
        class="card card-pad notification-card"
        :class="{ 'unread-card': !item.read_at }"
        @click="handleClick(item)"
      >
        <div class="notif-icon-box" :class="getNotifBg(item.type)">
          <component :is="getNotifIcon(item.type)" :size="20" />
        </div>

        <div class="notif-content truncate">
          <div class="notif-title-row">
            <span class="bold small text-dark">{{ item.title }}</span>
            <span class="small muted">{{ timeAgo(item.created_at) }}</span>
          </div>
          <p v-if="item.message" class="notif-message small text-2 mt-1">{{ item.message }}</p>
        </div>

        <div v-if="!item.read_at" class="unread-dot"></div>
      </div>
    </div>

    <div v-else class="card card-pad empty">
      <div class="empty-icon bg-green-50">
        <Bell :size="32" class="text-primary" />
      </div>
      <h3>No notifications</h3>
      <p class="small muted">New activities and recognitions will appear here.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import {
  Bell, CheckCheck, Heart, MessageCircle, Share2,
  Sparkles, Flame, Award, ShieldCheck, CheckCircle2
} from 'lucide-vue-next'
import api from '@/api/client'
import { useToastStore } from '@/stores/toast'

const router = useRouter()
const toastStore = useToastStore()

const loading = ref(true)
const items = ref([])
const unreadCount = ref(0)
const filter = ref('all')

const fetchNotifications = async () => {
  loading.value = true
  try {
    const res = await api.get('/notifications', {
      params: { filter: filter.value },
    })
    items.value = res.data.data.data
    unreadCount.value = res.data.unread
  } catch (err) {
    console.error('Failed notifications', err)
  } finally {
    loading.value = false
  }
}

const markAllAsRead = async () => {
  try {
    await api.post('/notifications/read-all')
    unreadCount.value = 0
    items.value.forEach((i) => (i.read_at = new Date().toISOString()))
    toastStore.success('All notifications marked as read.')
  } catch (err) {
    toastStore.error('Failed to mark notifications as read.')
  }
}

const handleClick = async (item) => {
  if (!item.read_at) {
    try {
      await api.post(`/notifications/${item.id}/read`)
      item.read_at = new Date().toISOString()
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    } catch (e) {
      // ignore
    }
  }
  if (item.link) {
    router.push(item.link)
  }
}

const getNotifIcon = (type) => {
  const map = {
    like: Heart,
    comment: MessageCircle,
    reply: MessageCircle,
    share: Share2,
    approved: CheckCircle2,
    rejected: ShieldCheck,
    level_up: Award,
    streak: Flame,
    review_request: ShieldCheck,
  }
  return map[type] || Sparkles
}

const getNotifBg = (type) => {
  const map = {
    like: 'bg-red-soft text-red',
    comment: 'bg-blue-soft text-blue',
    reply: 'bg-blue-soft text-blue',
    approved: 'bg-green-soft text-primary',
    rejected: 'bg-red-soft text-red',
    level_up: 'bg-amber-soft text-amber',
    streak: 'bg-orange-soft text-orange',
  }
  return map[type] || 'bg-green-soft text-primary'
}

onMounted(() => {
  fetchNotifications()
})

const timeAgo = (dateStr) => {
  if (!dateStr) return ''
  const diff = (Date.now() - new Date(dateStr).getTime()) / 1000
  if (diff < 60) return 'Just now'
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`
  if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`
  return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}
</script>

<style scoped src="@/styles/features/notifications.css"></style>
