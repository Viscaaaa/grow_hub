<template>
  <div class="page detail-page">
    <div v-if="loading" class="stack">
      <div class="skeleton" style="height: 40px; width: 200px;"></div>
      <div class="skeleton" style="height: 380px;"></div>
    </div>

    <div v-else-if="content" class="detail-split-layout">
      <!-- Main Content Area -->
      <article class="detail-main-col stack">
        <!-- Back Navigation -->
        <div class="row-between">
          <button class="btn btn-ghost btn-sm" @click="$router.back()">
            <ArrowLeft :size="16" />
            <span>Back</span>
          </button>

          <div class="row" style="gap: 8px;">
            <span
              v-if="content.category"
              class="badge"
              :style="{ background: `${content.category.color}18`, color: content.category.color }"
            >
              {{ content.category.name }}
            </span>
            <span v-if="content.community" class="badge badge-dark">
              {{ content.community.name }}
            </span>
          </div>
        </div>

        <!-- Article Card -->
        <div class="card card-pad article-card">
          <!-- Author Header -->
          <header class="detail-author-header mb-3">
            <router-link :to="`/profile/${content.author?.id}`" class="row" style="gap: 12px; text-decoration: none; color: inherit;">
              <div class="avatar" style="width: 48px; height: 48px; font-size: 16px;">
                <img v-if="content.author?.avatar_url" :src="content.author.avatar_url" :alt="content.author.name" />
                <span v-else>{{ initials(content.author?.name) }}</span>
              </div>
              <div>
                <div class="row" style="gap: 8px;">
                  <span class="bold author-name">{{ content.author?.name }}</span>
                  <span v-if="content.author?.department" class="badge badge-gray small">
                    {{ content.author.department }}
                  </span>
                </div>
                <div class="row muted small" style="gap: 8px;">
                  <span>NIP {{ content.author?.nip }}</span>
                  <span>•</span>
                  <span>{{ fullDate(content.published_at || content.created_at) }}</span>
                </div>
              </div>
            </router-link>

            <!-- Reviewer verification badge if published -->
            <div v-if="content.status === 'published' && content.reviewer" class="reviewed-badge" title="Validated by reviewer">
              <ShieldCheck :size="15" class="text-primary" />
              <span>Validated by {{ content.reviewer.name }}</span>
            </div>
          </header>

          <!-- Title -->
          <h1 class="detail-title mb-3">{{ content.title }}</h1>

          <!-- Body Content -->
          <div class="detail-body mb-4">
            {{ content.body }}
          </div>

          <!-- Media Attachments -->
          <div v-if="content.media && content.media.length > 0" class="detail-media-gallery mb-4">
            <!-- Images -->
            <div v-if="images.length > 0" class="image-gallery-grid">
              <div
                v-for="img in images"
                :key="img.id"
                class="gallery-img-wrapper"
                @click="lightboxImage = img.url"
              >
                <img :src="img.url" :alt="content.title" class="gallery-img" />
              </div>
            </div>

            <!-- Videos -->
            <div v-if="videos.length > 0" class="video-gallery">
              <video
                v-for="vid in videos"
                :key="vid.id"
                controls
                class="detail-video-player"
              >
                <source :src="vid.url" :type="vid.mime || 'video/mp4'" />
                Your browser does not support the video tag.
              </video>
            </div>

            <!-- Documents -->
            <div v-if="documents.length > 0" class="docs-box mt-3">
              <div class="bold small mb-2">Attached Documents / SOP:</div>
              <div class="docs-grid">
                <a
                  v-for="doc in documents"
                  :key="doc.id"
                  :href="doc.url"
                  target="_blank"
                  class="doc-tile"
                >
                  <FileText :size="24" class="text-primary" />
                  <div class="doc-tile-info truncate">
                    <span class="bold small truncate">{{ doc.original_name || 'Document' }}</span>
                    <span class="small muted">{{ formatBytes(doc.size) }}</span>
                  </div>
                  <Download :size="18" class="text-primary" />
                </a>
              </div>
            </div>
          </div>

          <!-- Tags -->
          <div v-if="content.tags && content.tags.length > 0" class="tags-row mb-4">
            <router-link
              v-for="tag in content.tags"
              :key="tag"
              :to="`/feed?q=%23${tag}`"
              class="tag-item"
            >
              #{{ tag }}
            </router-link>
          </div>

          <!-- Interactive Action Toolbar -->
          <footer class="detail-action-bar">
            <div class="row" style="gap: 8px;">
              <button
                class="btn btn-outline"
                :class="{ 'btn-liked': isLiked }"
                @click="toggleLike"
              >
                <Heart :size="18" :class="{ 'fill-red text-red pop': isLiked }" />
                <span>{{ isLiked ? 'Liked' : 'Like' }} ({{ likesCount }})</span>
              </button>

              <button
                class="btn btn-outline"
                :class="{ 'btn-saved': isSaved }"
                @click="toggleSave"
              >
                <Bookmark :size="18" :class="{ 'fill-primary text-primary': isSaved }" />
                <span>{{ isSaved ? 'Saved' : 'Save' }}</span>
              </button>

              <button class="btn btn-outline" @click="shareModalOpen = true">
                <Share2 :size="18" />
                <span>Share ({{ sharesCount }})</span>
              </button>
            </div>

            <div class="row muted small" style="gap: 6px;">
              <Eye :size="16" />
              <span>{{ content.views_count || 0 }} views</span>
            </div>
          </footer>
        </div>

        <!-- Threaded Comments Section -->
        <section id="comments" class="card card-pad comments-section">
          <div class="row-between mb-3">
            <div class="row" style="gap: 8px;">
              <MessageCircle :size="20" class="text-primary" />
              <h2 class="card-title">Discussion & Comments ({{ comments.length }})</h2>
            </div>
            <span class="badge badge-gray small">+1 Point per helpful comment</span>
          </div>

          <!-- Post comment box -->
          <form @submit.prevent="submitComment(null)" class="comment-input-box mb-4">
            <textarea
              v-model="newCommentText"
              rows="3"
              placeholder="Write your thoughts, appreciation, or questions for the author..."
              class="textarea"
              required
            ></textarea>
            <div class="row-between mt-2">
              <span class="small muted">Respect your colleagues and maintain professional etiquette</span>
              <button type="submit" class="btn btn-primary btn-sm" :disabled="commentSubmitting || !newCommentText.trim()">
                <span v-if="commentSubmitting" class="spinner"></span>
                <Send v-else :size="14" />
                <span>Post Comment</span>
              </button>
            </div>
          </form>

          <!-- Comments List -->
          <div v-if="comments.length > 0" class="comments-thread stack">
            <div
              v-for="cmt in comments"
              :key="cmt.id"
              class="comment-node"
            >
              <div class="comment-item">
                <router-link :to="`/profile/${cmt.user?.id}`" class="avatar" style="width: 36px; height: 36px; font-size: 13px;">
                  <img v-if="cmt.user?.avatar_url" :src="cmt.user.avatar_url" :alt="cmt.user.name" />
                  <span v-else>{{ initials(cmt.user?.name) }}</span>
                </router-link>

                <div class="comment-bubble">
                  <div class="row-between">
                    <div class="row" style="gap: 6px;">
                      <router-link :to="`/profile/${cmt.user?.id}`" class="bold small text-dark">
                        {{ cmt.user?.name }}
                      </router-link>
                      <span class="small muted">• {{ timeAgo(cmt.created_at) }}</span>
                    </div>

                    <button
                      v-if="cmt.user_id === authStore.user?.id || authStore.isAdmin"
                      class="btn btn-ghost btn-icon btn-sm text-red"
                      @click="deleteComment(cmt)"
                      title="Delete comment"
                    >
                      <Trash2 :size="14" />
                    </button>
                  </div>

                  <p class="comment-text">{{ cmt.body }}</p>

                  <button
                    class="btn btn-ghost btn-sm reply-btn"
                    @click="activeReplyId = activeReplyId === cmt.id ? null : cmt.id"
                  >
                    <span>Reply</span>
                  </button>
                </div>
              </div>

              <!-- Reply input if active -->
              <div v-if="activeReplyId === cmt.id" class="reply-input-box mt-2">
                <textarea
                  v-model="replyText"
                  rows="2"
                  :placeholder="`Reply to ${cmt.user?.name}...`"
                  class="textarea"
                ></textarea>
                <div class="row mt-1" style="justify-content: flex-end; gap: 8px;">
                  <button type="button" class="btn btn-ghost btn-sm" @click="activeReplyId = null">
                    Cancel
                  </button>
                  <button
                    type="button"
                    class="btn btn-primary btn-sm"
                    :disabled="!replyText.trim()"
                    @click="submitComment(cmt.id)"
                  >
                    Post Reply
                  </button>
                </div>
              </div>

              <!-- Nested replies -->
              <div v-if="cmt.replies && cmt.replies.length > 0" class="replies-list">
                <div
                  v-for="rep in cmt.replies"
                  :key="rep.id"
                  class="comment-item reply-item"
                >
                  <router-link :to="`/profile/${rep.user?.id}`" class="avatar" style="width: 30px; height: 30px; font-size: 11px;">
                    <img v-if="rep.user?.avatar_url" :src="rep.user.avatar_url" :alt="rep.user.name" />
                    <span v-else>{{ initials(rep.user?.name) }}</span>
                  </router-link>

                  <div class="comment-bubble">
                    <div class="row-between">
                      <span class="bold small text-dark">{{ rep.user?.name }}</span>
                      <span class="small muted">{{ timeAgo(rep.created_at) }}</span>
                    </div>
                    <p class="comment-text">{{ rep.body }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="empty py-4">
            <p class="small muted">No comments yet. Be the first to share your appreciation or thoughts!</p>
          </div>
        </section>
      </article>

      <!-- Sidebar Column: Author Card & Related Contents -->
      <aside class="detail-side-col stack">
        <!-- Author Profile Card -->
        <div class="card card-pad author-card">
          <div class="card-title mb-2">About the Author</div>
          <div class="author-card-header">
            <router-link :to="`/profile/${content.author?.id}`" class="avatar" style="width: 56px; height: 56px; font-size: 18px;">
              <img v-if="content.author?.avatar_url" :src="content.author.avatar_url" :alt="content.author.name" />
              <span v-else>{{ initials(content.author?.name) }}</span>
            </router-link>
            <div class="author-card-meta">
              <router-link :to="`/profile/${content.author?.id}`" class="bold author-name text-dark">
                {{ content.author?.name }}
              </router-link>
              <span class="small muted">{{ content.author?.position || 'Employee' }}</span>
              <span class="badge badge-gray small mt-1">{{ content.author?.department }}</span>
            </div>
          </div>

          <div class="divider"></div>

          <div class="row-between small">
            <span class="muted">Author's Total Points:</span>
            <span class="bold text-primary">{{ content.author?.points || 0 }} Pts</span>
          </div>

          <router-link :to="`/profile/${content.author?.id}`" class="btn btn-outline btn-block btn-sm mt-3">
            View Full Profile
          </router-link>
        </div>

        <!-- Related Articles in the same Category -->
        <div v-if="relatedArticles.length > 0" class="card card-pad related-card">
          <div class="card-title mb-3">Related in this Category</div>
          <div class="related-list">
            <router-link
              v-for="rel in relatedArticles"
              :key="rel.id"
              :to="`/content/${rel.id}`"
              class="related-item"
            >
              <span class="bold small clamp-2 text-dark hover-green">{{ rel.title }}</span>
              <div class="row-between small muted mt-1">
                <span>{{ rel.author?.name }}</span>
                <span>{{ rel.likes_count }} likes</span>
              </div>
            </router-link>
          </div>
        </div>
      </aside>
    </div>

    <!-- Lightbox Modal -->
    <div v-if="lightboxImage" class="modal-backdrop" @click="lightboxImage = null">
      <div class="lightbox-content">
        <img :src="lightboxImage" alt="Zoom" class="lightbox-img" />
      </div>
    </div>

    <!-- Share Modal -->
    <ShareModal
      :is-open="shareModalOpen"
      :content="content"
      @close="shareModalOpen = false"
      @shared="sharesCount = $event"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import {
  ArrowLeft, ShieldCheck, Heart, Bookmark, Share2, Eye,
  MessageCircle, Send, Trash2, FileText, Download
} from 'lucide-vue-next'
import api from '@/api/client'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import ShareModal from '@/components/common/ShareModal.vue'

const route = useRoute()
const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(true)
const content = ref(null)
const relatedArticles = ref([])
const comments = ref([])
const newCommentText = ref('')
const commentSubmitting = ref(false)
const activeReplyId = ref(null)
const replyText = ref('')

const isLiked = ref(false)
const likesCount = ref(0)
const isSaved = ref(false)
const sharesCount = ref(0)
const shareModalOpen = ref(false)
const lightboxImage = ref(null)

const images = computed(() => content.value?.media?.filter((m) => m.type === 'image') || [])
const videos = computed(() => content.value?.media?.filter((m) => m.type === 'video') || [])
const documents = computed(() => content.value?.media?.filter((m) => m.type === 'document') || [])

const fetchContent = async () => {
  loading.value = true
  try {
    const res = await api.get(`/contents/${route.params.id}`)
    content.value = res.data.content
    relatedArticles.value = res.data.related || []
    isLiked.value = Boolean(content.value.is_liked)
    likesCount.value = content.value.likes_count || 0
    isSaved.value = Boolean(content.value.is_saved)
    sharesCount.value = content.value.shares_count || 0
    await fetchComments()
  } catch (err) {
    toastStore.error('Failed to load content.')
  } finally {
    loading.value = false
  }
}

const fetchComments = async () => {
  try {
    const res = await api.get(`/contents/${route.params.id}/comments`)
    comments.value = res.data
  } catch (err) {
    console.error('Failed to load comments', err)
  }
}

const toggleLike = async () => {
  const prevLiked = isLiked.value
  const prevCount = likesCount.value
  isLiked.value = !prevLiked
  likesCount.value = prevLiked ? prevCount - 1 : prevCount + 1

  try {
    const res = await api.post(`/contents/${content.value.id}/like`)
    isLiked.value = res.data.liked
    likesCount.value = res.data.likes_count
    if (res.data.liked) {
      toastStore.pointsEarned(2, 'Content liked!')
    }
  } catch (err) {
    isLiked.value = prevLiked
    likesCount.value = prevCount
  }
}

const toggleSave = async () => {
  const prevSaved = isSaved.value
  isSaved.value = !prevSaved
  try {
    const res = await api.post(`/contents/${content.value.id}/save`)
    isSaved.value = res.data.saved
    toastStore.success(res.data.saved ? 'Saved to your collection.' : 'Removed from collection.')
  } catch (err) {
    isSaved.value = prevSaved
  }
}

const submitComment = async (parentId = null) => {
  const text = parentId ? replyText.value : newCommentText.value
  if (!text.trim()) return

  commentSubmitting.value = true
  try {
    const res = await api.post(`/contents/${content.value.id}/comments`, {
      body: text,
      parent_id: parentId,
    })

    if (res.data.points_earned > 0) {
      toastStore.pointsEarned(res.data.points_earned, 'Thank you for contributing to the discussion!')
      authStore.mutatePoints(res.data.points_earned)
    }

    if (parentId) {
      replyText.value = ''
      activeReplyId.value = null
    } else {
      newCommentText.value = ''
    }
    await fetchComments()
  } catch (err) {
    toastStore.error('Failed to post comment.')
  } finally {
    commentSubmitting.value = false
  }
}

const deleteComment = async (cmt) => {
  try {
    await api.delete(`/comments/${cmt.id}`)
    toastStore.success('Comment deleted.')
    await fetchComments()
  } catch (err) {
    toastStore.error('Failed to delete comment.')
  }
}

watch(
  () => route.params.id,
  () => {
    if (route.params.id) fetchContent()
  }
)

onMounted(() => {
  fetchContent()
})

const initials = (name) => {
  if (!name) return 'GH'
  return name.split(' ').map((n) => n[0]).slice(0, 2).join('').toUpperCase()
}

const formatBytes = (bytes) => {
  if (!bytes) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return `${parseFloat((bytes / Math.pow(k, i)).toFixed(1))} ${sizes[i]}`
}

const fullDate = (dateStr) => {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleString('en-US', { dateStyle: 'long' })
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

<style scoped src="@/styles/features/content-detail.css"></style>
