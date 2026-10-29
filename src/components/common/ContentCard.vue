<template>
  <article class="card card-pad card-hover content-card">
    <!-- Header: Author info & meta -->
    <header class="card-author-header">
      <router-link :to="`/profile/${item.author?.id}`" class="row author-link">
        <div class="avatar" style="width: 44px; height: 44px; font-size: 15px;">
          <img v-if="item.author?.avatar_url" :src="item.author.avatar_url" :alt="item.author.name" />
          <span v-else>{{ initials(item.author?.name) }}</span>
        </div>
        <div class="author-info">
          <div class="row" style="gap: 6px;">
            <span class="bold author-name">{{ item.author?.name }}</span>
            <span v-if="item.author?.department" class="badge badge-gray small">
              {{ item.author.department }}
            </span>
          </div>
          <div class="row muted small" style="gap: 8px;">
            <span>ID: {{ item.author?.nip }}</span>
            <span>•</span>
            <span :title="fullDate(item.published_at || item.created_at)">
              {{ timeAgo(item.published_at || item.created_at) }}
            </span>
          </div>
        </div>
      </router-link>

      <!-- Category badge -->
      <div class="row" style="gap: 8px;">
        <span
          v-if="item.category"
          class="badge"
          :style="{ background: `${item.category.color}18`, color: item.category.color }"
        >
          {{ item.category.name }}
        </span>
        <span v-if="item.community" class="badge badge-dark">
          {{ item.community.name }}
        </span>
      </div>
    </header>

    <!-- Body: Title & excerpt -->
    <router-link :to="`/content/${item.id}`" class="content-body-link">
      <h2 class="content-title clamp-2">{{ item.title }}</h2>
      <p class="content-excerpt clamp-3">{{ item.body }}</p>
    </router-link>

    <!-- Media display (images, video, documents) -->
    <div v-if="item.media && item.media.length > 0" class="media-container">
      <!-- Image gallery -->
      <div v-if="firstImage" class="media-image-wrapper">
        <img :src="firstImage.url" :alt="item.title" class="media-image" loading="lazy" />
        <span v-if="imageCount > 1" class="media-count-badge">
          +{{ imageCount - 1 }} more photos
        </span>
      </div>

      <!-- Video preview -->
      <div v-else-if="firstVideo" class="media-video-wrapper">
        <video controls class="media-video" preload="metadata">
          <source :src="firstVideo.url" :type="firstVideo.mime || 'video/mp4'" />
          Your browser does not support the video tag.
        </video>
      </div>

      <!-- Documents list -->
      <div v-if="documents.length > 0" class="docs-list">
        <a
          v-for="doc in documents"
          :key="doc.id"
          :href="doc.url"
          target="_blank"
          class="doc-item"
        >
          <FileText :size="20" class="text-primary" />
          <div class="doc-item-info">
            <span class="doc-name truncate">{{ doc.original_name || 'Document' }}</span>
            <span class="muted small">{{ formatBytes(doc.size) }}</span>
          </div>
          <Download :size="16" class="muted" />
        </a>
      </div>
    </div>

    <!-- Tags -->
    <div v-if="item.tags && item.tags.length > 0" class="tags-row">
      <router-link
        v-for="tag in item.tags"
        :key="tag"
        :to="`/feed?q=%23${tag}`"
        class="tag-item"
      >
        #{{ tag }}
      </router-link>
    </div>

    <!-- Footer: Interactive Actions (Like, Comment, Share, Save) -->
    <footer class="content-actions">
      <!-- Like -->
      <button
        class="action-btn"
        :class="{ active: isLiked }"
        @click.prevent="handleLike"
        :disabled="likeLoading"
      >
        <Heart :size="18" :class="{ 'fill-red text-red pop': isLiked }" />
        <span>{{ likesCount }}</span>
      </button>

      <!-- Comment -->
      <router-link :to="`/content/${item.id}#comments`" class="action-btn">
        <MessageCircle :size="18" />
        <span>{{ item.comments_count || 0 }}</span>
      </router-link>

      <!-- Share -->
      <button class="action-btn" @click.prevent="openShareModal">
        <Share2 :size="18" />
        <span>{{ sharesCount }}</span>
      </button>

      <!-- Save -->
      <button
        class="action-btn"
        :class="{ active: isSaved }"
        @click.prevent="handleSave"
        :disabled="saveLoading"
      >
        <Bookmark :size="18" :class="{ 'fill-primary text-primary': isSaved }" />
        <span>{{ isSaved ? 'Saved' : 'Save' }}</span>
      </button>

      <!-- Views stat -->
      <div class="views-stat muted small">
        <Eye :size="14" />
        <span>{{ item.views_count || 0 }}</span>
      </div>
    </footer>

    <!-- Share Modal -->
    <ShareModal
      :is-open="isShareOpen"
      :content="item"
      @close="isShareOpen = false"
      @shared="onContentShared"
    />
  </article>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Heart, MessageCircle, Share2, Bookmark, Eye, FileText, Download } from 'lucide-vue-next'
import api from '@/api/client'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import ShareModal from './ShareModal.vue'

const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
})

const authStore = useAuthStore()
const toastStore = useToastStore()

const isLiked = ref(Boolean(props.item.is_liked))
const likesCount = ref(props.item.likes_count || 0)
const likeLoading = ref(false)

const isSaved = ref(Boolean(props.item.is_saved))
const saveLoading = ref(false)

const sharesCount = ref(props.item.shares_count || 0)
const isShareOpen = ref(false)

// Media groupings
const firstImage = computed(() => {
  return props.item.media?.find((m) => m.type === 'image')
})

const imageCount = computed(() => {
  return props.item.media?.filter((m) => m.type === 'image').length || 0
})

const firstVideo = computed(() => {
  return props.item.media?.find((m) => m.type === 'video')
})

const documents = computed(() => {
  return props.item.media?.filter((m) => m.type === 'document') || []
})

// Handlers
const handleLike = async () => {
  if (likeLoading.value) return
  likeLoading.value = true
  const prevLiked = isLiked.value
  const prevCount = likesCount.value

  // Optimistic update
  isLiked.value = !prevLiked
  likesCount.value = prevLiked ? Math.max(0, prevCount - 1) : prevCount + 1

  try {
    const res = await api.post(`/contents/${props.item.id}/like`)
    isLiked.value = res.data.liked
    likesCount.value = res.data.likes_count
    if (res.data.liked) {
      toastStore.pointsEarned(2, 'Content appreciated!')
    }
  } catch (err) {
    // Revert
    isLiked.value = prevLiked
    likesCount.value = prevCount
    toastStore.error('Failed to like content.')
  } finally {
    likeLoading.value = false
  }
}

const handleSave = async () => {
  if (saveLoading.value) return
  saveLoading.value = true
  const prevSaved = isSaved.value

  isSaved.value = !prevSaved
  try {
    const res = await api.post(`/contents/${props.item.id}/save`)
    isSaved.value = res.data.saved
    if (res.data.saved) {
      toastStore.success('Content saved to your bookmarks.')
    } else {
      toastStore.info('Removed from saved bookmarks.')
    }
  } catch (err) {
    isSaved.value = prevSaved
    toastStore.error('Failed to save content.')
  } finally {
    saveLoading.value = false
  }
}

const openShareModal = () => {
  isShareOpen.value = true
}

const onContentShared = (newCount) => {
  sharesCount.value = newCount
}

// Helpers
const initials = (name) => {
  if (!name) return 'GH'
  return name
    .split(' ')
    .map((n) => n[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()
}

const formatBytes = (bytes) => {
  if (!bytes) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return `${parseFloat((bytes / Math.pow(k, i)).toFixed(1))} ${sizes[i]}`
}

const fullDate = (dateStr) => {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleString('en-US', {
    dateStyle: 'medium',
    timeStyle: 'short',
  })
}

const timeAgo = (dateStr) => {
  if (!dateStr) return ''
  const diff = (Date.now() - new Date(dateStr).getTime()) / 1000
  if (diff < 60) return 'Just now'
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`
  if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`
  if (diff < 604800) return `${Math.floor(diff / 86400)}d ago`
  return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}
</script>

<style scoped src="@/styles/components/content-card.css"></style>
