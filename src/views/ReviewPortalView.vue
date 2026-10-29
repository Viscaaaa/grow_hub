<template>
  <div class="page review-page">
    <div class="page-header">
      <div>
        <div class="badge badge-dark mb-1">
          <ShieldCheck :size="14" />
          <span>Reviewer Validation & Curation Portal</span>
        </div>
        <h1 class="page-title">Employee Content Validation</h1>
        <p class="page-subtitle">
          Review relevance, quality, and alignment of employee contributions before publishing company-wide.
        </p>
      </div>
    </div>

    <!-- Stats summary -->
    <div class="grid grid-4 mb-4">
      <div class="card card-pad stat">
        <div class="stat-icon bg-amber-50">
          <Clock :size="20" class="text-amber" />
        </div>
        <div>
          <div class="stat-value">{{ stats.pending || 0 }}</div>
          <div class="stat-label">Pending Queue</div>
        </div>
      </div>

      <div class="card card-pad stat">
        <div class="stat-icon bg-green-50">
          <CheckCircle2 :size="20" class="text-primary" />
        </div>
        <div>
          <div class="stat-value">{{ stats.published || 0 }}</div>
          <div class="stat-label">Published</div>
        </div>
      </div>

      <div class="card card-pad stat">
        <div class="stat-icon bg-red-50">
          <AlertCircle :size="20" class="text-red" />
        </div>
        <div>
          <div class="stat-value">{{ stats.rejected || 0 }}</div>
          <div class="stat-label">Rejected / Revision</div>
        </div>
      </div>

      <div class="card card-pad stat">
        <div class="stat-icon bg-blue-50">
          <Sparkles :size="20" class="text-blue" />
        </div>
        <div>
          <div class="stat-value">{{ stats.reviewed_today || 0 }}</div>
          <div class="stat-label">Reviewed Today</div>
        </div>
      </div>
    </div>

    <!-- Filter Tabs -->
    <div class="tabs mb-4">
      <button
        class="tab"
        :class="{ active: currentStatus === 'pending' }"
        @click="switchStatus('pending')"
      >
        <span>Pending Review</span>
        <span class="count">{{ stats.pending || 0 }}</span>
      </button>

      <button
        class="tab"
        :class="{ active: currentStatus === 'published' }"
        @click="switchStatus('published')"
      >
        <span>Published</span>
      </button>

      <button
        class="tab"
        :class="{ active: currentStatus === 'rejected' }"
        @click="switchStatus('rejected')"
      >
        <span>Rejected</span>
      </button>

      <button
        class="tab"
        :class="{ active: currentStatus === 'all' }"
        @click="switchStatus('all')"
      >
        <span>All History</span>
      </button>
    </div>

    <!-- Review Queue List -->
    <div v-if="loading" class="stack">
      <div class="skeleton" style="height: 200px;"></div>
      <div class="skeleton" style="height: 200px;"></div>
    </div>

    <div v-else-if="items.length > 0" class="stack review-list">
      <div
        v-for="item in items"
        :key="item.id"
        class="card card-pad review-card"
        :class="{ 'card-pending-border': item.status === 'pending' }"
      >
        <!-- Card Top Bar: Author & Category -->
        <div class="row-between mb-2">
          <div class="row" style="gap: 12px;">
            <div class="avatar" style="width: 40px; height: 40px; font-size: 14px;">
              <img v-if="item.author?.avatar_url" :src="item.author.avatar_url" :alt="item.author.name" />
              <span v-else>{{ initials(item.author?.name) }}</span>
            </div>
            <div>
              <div class="row" style="gap: 8px;">
                <span class="bold text-dark">{{ item.author?.name }}</span>
                <span class="badge badge-gray small">{{ item.author?.department }}</span>
              </div>
              <div class="small muted">
                NIP {{ item.author?.nip }} • Submitted {{ timeAgo(item.created_at) }}
              </div>
            </div>
          </div>

          <div class="row" style="gap: 8px;">
            <span
              v-if="item.category"
              class="badge"
              :style="{ background: `${item.category.color}18`, color: item.category.color }"
            >
              {{ item.category.name }}
            </span>
            <span v-if="item.status === 'published'" class="badge badge-green">Published</span>
            <span v-else-if="item.status === 'rejected'" class="badge badge-red">Rejected</span>
            <span v-else class="badge badge-amber">Pending Review</span>
          </div>
        </div>

        <!-- Title & Content Preview -->
        <h2 class="review-title mb-2">{{ item.title }}</h2>
        <p class="review-body mb-3">{{ item.body }}</p>

        <!-- Media Preview -->
        <div v-if="item.media && item.media.length > 0" class="review-media-preview mb-3">
          <div class="bold small mb-1">Media Attachments ({{ item.media.length }} files):</div>
          <div class="row" style="gap: 8px; flex-wrap: wrap;">
            <a
              v-for="m in item.media"
              :key="m.id"
              :href="m.url"
              target="_blank"
              class="media-pill"
            >
              <FileText :size="14" class="text-primary" />
              <span class="truncate" style="max-width: 180px;">{{ m.original_name || m.type }}</span>
            </a>
          </div>
        </div>

        <!-- Tags -->
        <div v-if="item.tags && item.tags.length > 0" class="chips mb-3">
          <span v-for="t in item.tags" :key="t" class="badge badge-gray small">#{{ t }}</span>
        </div>

        <!-- Reviewer decision bar / note -->
        <div v-if="item.status === 'rejected' && item.review_note" class="reviewer-note-box mb-3">
          <span class="bold small text-red">Reason for Rejection:</span>
          <p class="small text-dark mt-1">{{ item.review_note }}</p>
        </div>

        <!-- Action Footer (if pending) -->
        <div v-if="item.status === 'pending'" class="review-action-footer">
          <div class="small muted">
            Is this submission relevant, helpful, and aligned with workplace standards?
          </div>

          <div class="row" style="gap: 10px;">
            <button
              class="btn btn-outline text-red"
              @click="openRejectModal(item)"
              :disabled="actionLoading"
            >
              <X :size="16" />
              <span>Reject (Needs Revision)</span>
            </button>

            <button
              class="btn btn-primary"
              @click="approveContent(item)"
              :disabled="actionLoading"
            >
              <Check :size="16" />
              <span>Approve & Publish</span>
            </button>
          </div>
        </div>

        <!-- View content link if published -->
        <div v-else class="row-between pt-2" style="border-top: 1px solid var(--border);">
          <span class="small muted">
            Reviewed by {{ item.reviewer?.name || 'Reviewer' }}
          </span>
          <router-link
            v-if="item.status === 'published'"
            :to="`/content/${item.id}`"
            class="btn btn-outline btn-sm"
          >
            <Eye :size="14" />
            <span>View in Feed</span>
          </router-link>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="card card-pad empty">
      <div class="empty-icon bg-green-50">
        <CheckCircle2 :size="32" class="text-primary" />
      </div>
      <h3>No content queue under this status</h3>
      <p class="small muted">All employee submissions have been reviewed. Great job!</p>
    </div>

    <!-- Reject Reason Modal -->
    <div v-if="rejectModalOpen" class="modal-backdrop" @click.self="rejectModalOpen = false">
      <div class="modal">
        <div class="modal-head">
          <div class="card-title text-red">
            <AlertCircle :size="20" />
            <span>Reject Employee Submission</span>
          </div>
          <button class="btn btn-ghost btn-icon btn-sm" @click="rejectModalOpen = false">
            <X :size="18" />
          </button>
        </div>

        <form @submit.prevent="confirmReject">
          <div class="modal-body stack">
            <p class="small text-2">
              Provide clear and constructive feedback so the author understands the needed revisions:
            </p>

            <div class="field">
              <label class="label">Reviewer Note <span class="text-red">*</span></label>
              <textarea
                v-model="rejectNote"
                rows="4"
                placeholder="e.g. The SOP reference lacks the updated 2026 revision guidelines, please update..."
                class="textarea"
                required
              ></textarea>
            </div>
          </div>

          <div class="modal-foot">
            <button type="button" class="btn btn-outline" @click="rejectModalOpen = false">
              Cancel
            </button>
            <button type="submit" class="btn btn-danger" :disabled="!rejectNote.trim() || actionLoading">
              <span v-if="actionLoading" class="spinner"></span>
              <span v-else>Confirm Rejection</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import {
  ShieldCheck, Clock, CheckCircle2, AlertCircle, Sparkles,
  FileText, Check, X, Eye
} from 'lucide-vue-next'
import api from '@/api/client'
import { useToastStore } from '@/stores/toast'
import { useAuthStore } from '@/stores/auth'

const toastStore = useToastStore()
const authStore = useAuthStore()

const loading = ref(true)
const actionLoading = ref(false)
const items = ref([])
const stats = ref({})
const currentStatus = ref('pending')

const rejectModalOpen = ref(false)
const rejectingItem = ref(null)
const rejectNote = ref('')

const fetchQueue = async () => {
  loading.value = true
  try {
    const res = await api.get('/review/contents', {
      params: { status: currentStatus.value },
    })
    items.value = res.data.data.data
    stats.value = res.data.stats
  } catch (err) {
    console.error('Failed review queue', err)
  } finally {
    loading.value = false
  }
}

const switchStatus = (status) => {
  currentStatus.value = status
  fetchQueue()
}

const approveContent = async (item) => {
  actionLoading.value = true
  try {
    const res = await api.post(`/review/contents/${item.id}/approve`, {
      note: 'Content approved for publication.',
    })
    const authorPts = Number(res.data.author_points_awarded) || 0
    const reviewerPts = Number(res.data.reviewer_points_awarded) || 0

    const authorText = authorPts > 0 ? ` (Penulis +${authorPts} Poin)` : ''
    if (reviewerPts > 0) {
      toastStore.pointsEarned(reviewerPts, `"${item.title}" disetujui & dipublikasikan!${authorText} (+${reviewerPts} Poin Reviewer)`)
      authStore.mutatePoints(reviewerPts)
    } else {
      toastStore.success(`"${item.title}" disetujui & dipublikasikan!${authorText}`)
      authStore.fetchMe()
    }
    await fetchQueue()
  } catch (err) {
    toastStore.error(err.response?.data?.message || 'Gagal menyetujui konten.')
  } finally {
    actionLoading.value = false
  }
}

const openRejectModal = (item) => {
  rejectingItem.value = item
  rejectNote.value = ''
  rejectModalOpen.value = true
}

const confirmReject = async () => {
  if (!rejectNote.value.trim() || !rejectingItem.value) return
  actionLoading.value = true
  try {
    const res = await api.post(`/review/contents/${rejectingItem.value.id}/reject`, {
      note: rejectNote.value,
    })
    const reviewerPts = Number(res.data.reviewer_points_awarded) || 0
    if (reviewerPts > 0) {
      toastStore.pointsEarned(reviewerPts, `Konten ditolak & catatan revisi terkirim (+${reviewerPts} Poin Reviewer)`)
      authStore.mutatePoints(reviewerPts)
    } else {
      toastStore.info('Konten ditolak. Catatan revisi telah dikirim ke penulis.')
      authStore.fetchMe()
    }
    rejectModalOpen.value = false
    await fetchQueue()
  } catch (err) {
    toastStore.error(err.response?.data?.message || 'Gagal menolak konten.')
  } finally {
    actionLoading.value = false
  }
}

onMounted(() => {
  fetchQueue()
})

const initials = (name) => {
  if (!name) return 'GH'
  return name.split(' ').map((n) => n[0]).slice(0, 2).join('').toUpperCase()
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

<style scoped src="@/styles/features/review-portal.css"></style>
