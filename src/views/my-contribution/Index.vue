<template>
  <div class="page contributions-page">
    <div class="page-header">
      <div>
        <h1 class="page-title">My Contributions</h1>
        <p class="page-subtitle">Manage and monitor all your shared content, drafts, and review statuses.</p>
      </div>

      <router-link to="/contributions/create" class="btn btn-primary">
        <Plus :size="18" />
        <span>Create New Content</span>
      </router-link>
    </div>

    <!-- Engagement Overview Cards -->
    <div class="grid grid-4 mb-4">
      <div class="card card-pad stat">
        <div class="stat-icon bg-green-50">
          <BookOpen :size="20" class="text-primary" />
        </div>
        <div>
          <div class="stat-value">{{ counts.published || 0 }}</div>
          <div class="stat-label">Published Contents</div>
        </div>
      </div>

      <div class="card card-pad stat">
        <div class="stat-icon bg-red-50">
          <Heart :size="20" class="text-red" />
        </div>
        <div>
          <div class="stat-value">{{ engagement.likes || 0 }}</div>
          <div class="stat-label">Total Likes Received</div>
        </div>
      </div>

      <div class="card card-pad stat">
        <div class="stat-icon bg-blue-50">
          <MessageCircle :size="20" class="text-blue" />
        </div>
        <div>
          <div class="stat-value">{{ engagement.comments || 0 }}</div>
          <div class="stat-label">Total Comments</div>
        </div>
      </div>

      <div class="card card-pad stat">
        <div class="stat-icon bg-amber-50">
          <Eye :size="20" class="text-amber" />
        </div>
        <div>
          <div class="stat-value">{{ engagement.views || 0 }}</div>
          <div class="stat-label">Total Readers (Views)</div>
        </div>
      </div>
    </div>

    <!-- Status Tabs -->
    <div class="tabs mb-4">
      <button
        class="tab"
        :class="{ active: currentTab === 'all' }"
        @click="switchTab('all')"
      >
        <span>All</span>
        <span class="count">{{ counts.all || 0 }}</span>
      </button>

      <button
        class="tab"
        :class="{ active: currentTab === 'published' }"
        @click="switchTab('published')"
      >
        <span>Published</span>
        <span class="count">{{ counts.published || 0 }}</span>
      </button>

      <button
        class="tab"
        :class="{ active: currentTab === 'pending' }"
        @click="switchTab('pending')"
      >
        <span>In Review</span>
        <span class="count">{{ counts.pending || 0 }}</span>
      </button>

      <button
        class="tab"
        :class="{ active: currentTab === 'rejected' }"
        @click="switchTab('rejected')"
      >
        <span>Needs Revision</span>
        <span class="count">{{ counts.rejected || 0 }}</span>
      </button>

      <button
        class="tab"
        :class="{ active: currentTab === 'draft' }"
        @click="switchTab('draft')"
      >
        <span>Drafts</span>
        <span class="count">{{ counts.draft || 0 }}</span>
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="card card-pad text-center py-5">
      <span class="spinner" style="width: 28px; height: 28px; margin: 0 auto 12px; display: block;"></span>
      <p class="muted">Loading contributions...</p>
    </div>

    <!-- Contributions List -->
    <div v-else-if="items.length > 0" class="stack mb-4">
      <div
        v-for="item in items"
        :key="item.id"
        class="card card-pad contribution-card"
      >
        <!-- Card Header: Category & Status Badge -->
        <div class="row-between mb-2">
          <div class="row" style="gap: 8px;">
            <span
              v-if="item.category"
              class="badge"
              :style="{ background: `${item.category.color}15`, color: item.category.color }"
            >
              {{ item.category.name }}
            </span>

            <span v-if="item.status === 'published'" class="badge badge-primary">
              <CheckCircle2 :size="12" />
              <span>Published</span>
            </span>
            <span v-else-if="item.status === 'pending'" class="badge badge-amber">
              <Clock :size="12" />
              <span>Pending Reviewer Validation</span>
            </span>
            <span v-else-if="item.status === 'rejected'" class="badge badge-red">
              <AlertCircle :size="12" />
              <span>Needs Revision</span>
            </span>
            <span v-else class="badge badge-gray">
              <FileEdit :size="12" />
              <span>Draft</span>
            </span>
          </div>

          <span class="small muted">
            {{ fullDate(item.updated_at) }}
          </span>
        </div>

        <!-- Title & Excerpt -->
        <h2 class="contribution-title clamp-2">{{ item.title }}</h2>
        <p class="contribution-body clamp-2 small text-2 mb-2">{{ item.body }}</p>

        <!-- Reviewer feedback alert if rejected -->
        <div v-if="item.status === 'rejected' && item.review_note" class="reviewer-note-box mb-3">
          <div class="row small bold text-red" style="gap: 6px;">
            <AlertCircle :size="16" />
            <span>Note from Reviewer Team ({{ item.reviewer?.name || 'Reviewer' }}):</span>
          </div>
          <p class="small mt-1 text-dark">{{ item.review_note }}</p>
        </div>

        <!-- Card Footer -->
        <div class="row-between contribution-foot">
          <div class="row small muted" style="gap: 16px;">
            <span class="row" style="gap: 4px;"><Heart :size="14" /> {{ item.likes_count || 0 }}</span>
            <span class="row" style="gap: 4px;"><MessageCircle :size="14" /> {{ item.comments_count || 0 }}</span>
            <span class="row" style="gap: 4px;"><Share2 :size="14" /> {{ item.shares_count || 0 }}</span>
            <span class="row" style="gap: 4px;"><Eye :size="14" /> {{ item.views_count || 0 }}</span>
          </div>

          <div class="row" style="gap: 8px;">
            <!-- View link if published or pending -->
            <router-link
              v-if="item.status === 'published' || item.status === 'pending'"
              :to="`/content/${item.id}`"
              class="btn btn-outline btn-sm"
            >
              <Eye :size="14" />
              <span>View Content</span>
            </router-link>

            <!-- Edit Draft -->
            <router-link
              v-if="item.status === 'draft'"
              :to="`/contributions/${item.id}/edit`"
              class="btn btn-outline btn-sm"
            >
              <SquarePen :size="14" />
              <span>Edit Draft</span>
            </router-link>

            <!-- Revise & Resubmit if rejected -->
            <router-link
              v-if="item.status === 'rejected'"
              :to="`/contributions/${item.id}/edit`"
              class="btn btn-primary btn-sm"
            >
              <SquarePen :size="14" />
              <span>Revise & Resubmit</span>
            </router-link>

            <!-- Delete -->
            <button
              class="btn btn-ghost btn-sm text-red"
              @click="openDeleteModal(item)"
              title="Delete"
            >
              <Trash2 :size="14" />
              <span>Delete</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="card card-pad empty">
      <div class="empty-icon">
        <SquarePen :size="32" />
      </div>
      <h3>No content in this tab yet</h3>
      <p class="small muted">Start sharing your knowledge and earn appreciation points!</p>
      <router-link to="/contributions/create" class="btn btn-primary btn-sm mt-3">
        <Plus :size="16" />
        <span>Create New Content</span>
      </router-link>
    </div>

    <!-- Custom Delete Confirmation Modal -->
    <div v-if="deleteModalOpen" class="modal-backdrop" @click.self="closeDeleteModal">
      <div class="modal" style="max-width: 440px;">
        <div class="modal-head">
          <div class="card-title text-red">
            <Trash2 :size="20" />
            <span>Hapus Konten / Draft</span>
          </div>
          <button class="btn btn-ghost btn-icon btn-sm" @click="closeDeleteModal" :disabled="deleting">
            <X :size="18" />
          </button>
        </div>

        <div class="modal-body stack">
          <p class="text-dark" style="font-size: 15px; line-height: 1.5;">
            Apakah Anda yakin ingin menghapus <strong>"{{ itemToDelete?.title }}"</strong>?
          </p>
          <div class="card card-pad bg-red-50 border-red small text-red" style="padding: 10px 14px;">
            Tindakan ini permanen dan tidak dapat dibatalkan. Konten beserta seluruh lampiran media terkait akan dihapus dari sistem.
          </div>
        </div>

        <div class="modal-foot">
          <button type="button" class="btn btn-outline" @click="closeDeleteModal" :disabled="deleting">
            Batal
          </button>
          <button type="button" class="btn btn-danger" @click="confirmDelete" :disabled="deleting">
            <span v-if="deleting" class="spinner"></span>
            <span v-else class="row" style="gap: 6px;">
              <Trash2 :size="15" />
              <span>Hapus Permanen</span>
            </span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import {
  Plus, BookOpen, Heart, MessageCircle, Eye, Share2,
  CheckCircle2, Clock, AlertCircle, FileEdit, Trash2, SquarePen, X
} from 'lucide-vue-next'
import api from '@/api/client'
import { useToastStore } from '@/stores/toast'

const route = useRoute()
const toastStore = useToastStore()

const loading = ref(true)
const items = ref([])
const counts = ref({})
const engagement = ref({})
const currentTab = ref(route.query.status || 'all')

const fetchContributions = async () => {
  loading.value = true
  try {
    const res = await api.get('/my/contents', {
      params: { status: currentTab.value },
    })
    items.value = res.data.data.data
    counts.value = res.data.counts
    engagement.value = res.data.engagement || {}
  } catch (err) {
    console.error('Failed to load contributions', err)
  } finally {
    loading.value = false
  }
}

const switchTab = (tab) => {
  currentTab.value = tab
  fetchContributions()
}

const deleteModalOpen = ref(false)
const itemToDelete = ref(null)
const deleting = ref(false)

const openDeleteModal = (item) => {
  itemToDelete.value = item
  deleteModalOpen.value = true
}

const closeDeleteModal = () => {
  if (deleting.value) return
  deleteModalOpen.value = false
  itemToDelete.value = null
}

const confirmDelete = async () => {
  if (!itemToDelete.value) return
  const id = itemToDelete.value.id
  deleting.value = true
  try {
    await api.delete(`/contents/${id}`)
    toastStore.success('Konten berhasil dihapus.')
    deleteModalOpen.value = false
    itemToDelete.value = null
    await fetchContributions()
  } catch (err) {
    toastStore.error(err.response?.data?.message || 'Gagal menghapus konten.')
  } finally {
    deleting.value = false
  }
}

const fullDate = (iso) => {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

onMounted(() => {
  fetchContributions()
})
</script>

<style scoped src="@/styles/features/my-contribution.css"></style>
