<template>
  <div class="page create-page">
    <!-- 1. Dedicated Initial Loading State (Prevents Blinking / Flashing) -->
    <div v-if="loading" class="stack">
      <div class="page-header">
        <div>
          <router-link to="/contributions" class="btn btn-ghost btn-sm mb-2" style="padding-left: 0;">
            <ArrowLeft :size="16" />
            <span>Back to My Contributions</span>
          </router-link>
          <h1 class="page-title">Loading Draft Content...</h1>
          <p class="page-subtitle">Retrieving your saved draft details and media attachments.</p>
        </div>
      </div>

      <div class="card card-pad text-center py-5">
        <span class="spinner" style="width: 32px; height: 32px; margin: 0 auto 16px; display: block;"></span>
        <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 6px;">Loading draft details...</h3>
        <p class="small muted">Please wait a moment while we load your content.</p>
      </div>
    </div>

    <!-- 2. Error State -->
    <div v-else-if="loadError" class="stack">
      <div class="page-header">
        <router-link to="/contributions" class="btn btn-ghost btn-sm mb-2" style="padding-left: 0;">
          <ArrowLeft :size="16" />
          <span>Back to My Contributions</span>
        </router-link>
      </div>

      <div class="card card-pad text-center py-5">
        <div class="empty-icon text-red" style="margin: 0 auto 16px;">
          <AlertCircle :size="36" />
        </div>
        <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 8px;">Content Cannot Be Edited</h3>
        <p class="muted small mb-3">{{ loadError }}</p>
        <router-link to="/contributions" class="btn btn-primary btn-sm">
          Back to My Contributions
        </router-link>
      </div>
    </div>

    <!-- 3. Loaded Form (Zero Blinking) -->
    <div v-else class="stack">
      <div class="page-header">
        <div>
          <router-link to="/contributions" class="btn btn-ghost btn-sm mb-2" style="padding-left: 0;">
            <ArrowLeft :size="16" />
            <span>Back to My Contributions</span>
          </router-link>
          <h1 class="page-title">
            {{ editStatus === 'rejected' ? 'Revise & Resubmit Content' : 'Edit Draft Content' }}
          </h1>
          <p class="page-subtitle">
            {{ editStatus === 'rejected'
              ? 'Update your content based on reviewer feedback and resubmit for curation.'
              : 'Continue writing your draft, update media attachments, or submit to the curation team.'
            }}
          </p>
        </div>
      </div>

      <!-- Reviewer Feedback Alert if Rejected -->
      <div v-if="editStatus === 'rejected' && reviewNote" class="card card-pad bg-red-50 border-red mb-4">
        <div class="row items-start" style="gap: 12px;">
          <AlertCircle :size="22" class="text-red mt-1" style="flex-shrink: 0;" />
          <div>
            <div class="bold text-red" style="font-size: 15px;">Catatan Revisi dari Reviewer Team:</div>
            <p class="text-dark mt-1" style="font-size: 14px; line-height: 1.5; white-space: pre-wrap;">{{ reviewNote }}</p>
            <p class="small muted mt-2">Silakan perbaiki bagian yang disebutkan di atas lalu klik tombol <strong>"Resubmit for Review"</strong> di samping.</p>
          </div>
        </div>
      </div>

      <form @submit.prevent="handleSubmit('submit')" class="create-form">
        <div class="grid create-grid">
          <!-- Main Form Column (Left) -->
          <div class="create-main-col stack">
            <!-- Title -->
            <div class="card card-pad field">
              <label class="label">Content Title <span class="text-red">*</span></label>
              <input
                v-model="form.title"
                type="text"
                placeholder="e.g. 5 Ways to Expedite In-Store Stock Opname..."
                class="input title-input"
                required
                maxlength="200"
              />
              <div class="row-between small muted mt-1">
                <span>Write a clear, engaging, and informative title</span>
                <span>{{ form.title.length }}/200</span>
              </div>
            </div>

            <!-- Body -->
            <div class="card card-pad field">
              <div class="row-between mb-1">
                <label class="label">Knowledge Content / Discussion <span class="text-red">*</span></label>
                <div class="small muted">Supports paragraphs & bullet points</div>
              </div>
              <textarea
                v-model="form.body"
                rows="10"
                placeholder="Clearly describe the background, actionable steps, challenges encountered, and positive results or impact achieved for the company..."
                class="textarea body-textarea"
                required
              ></textarea>
              <div class="row-between small muted mt-1">
                <span>Minimum 20 characters for meaningful knowledge sharing</span>
                <span>{{ form.body.length }} characters</span>
              </div>
            </div>

            <!-- Media Section -->
            <div class="card card-pad field">
              <div class="row-between mb-2">
                <div>
                  <label class="label">Attach Media (Photos, Videos, or Documents / SOP)</label>
                  <p class="small text-2">Supports multi-file upload: JPG, PNG, MP4, PDF, DOCX, XLSX (max 50 MB/file)</p>
                </div>
                <span class="badge badge-gray">{{ totalMediaCount }} Files Attached</span>
              </div>

              <!-- Current Saved Attachments -->
              <div v-if="existingMedia.length > 0" class="mb-3">
                <div class="small bold muted mb-2">Current Saved Attachments ({{ existingMedia.length }}):</div>
                <div class="files-preview-list">
                  <div
                    v-for="mediaItem in existingMedia"
                    :key="mediaItem.id"
                    class="file-preview-card"
                  >
                    <div class="file-thumb">
                      <img
                        v-if="mediaItem.type === 'image' || mediaItem.mime?.startsWith('image/')"
                        :src="mediaItem.url"
                        alt="Thumbnail"
                        class="thumb-img"
                      />
                      <Video v-else-if="mediaItem.type === 'video' || mediaItem.mime?.startsWith('video/')" :size="22" class="text-primary" />
                      <FileText v-else :size="22" class="text-primary" />
                    </div>

                    <div class="file-info truncate">
                      <span class="bold small truncate">{{ mediaItem.original_name || 'Media attachment' }}</span>
                      <span class="small muted">{{ formatBytes(mediaItem.size) }} &bull; Previously uploaded</span>
                    </div>

                    <button
                      type="button"
                      class="btn btn-ghost btn-icon btn-sm text-red"
                      @click="removeExistingMedia(mediaItem.id)"
                      title="Remove this attachment"
                    >
                      <X :size="16" />
                    </button>
                  </div>
                </div>
              </div>

              <!-- Drag & Drop Zone for New Files -->
              <div
                class="dropzone"
                :class="{ 'dropzone-active': isDragging }"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="handleDrop"
                @click="$refs.fileInput.click()"
              >
                <input
                  ref="fileInput"
                  type="file"
                  multiple
                  accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt"
                  class="hidden-input"
                  @change="handleFileSelect"
                />
                <div class="dropzone-inner">
                  <div class="dropzone-icon">
                    <UploadCloud :size="28" class="text-primary" />
                  </div>
                  <div class="bold">Click or drag additional media files here</div>
                  <span class="small muted">Upload new display photos, short videos, or SOP documents</span>
                </div>
              </div>

              <!-- New Attached Files Preview List -->
              <div v-if="files.length > 0" class="files-preview-list mt-3">
                <div class="small bold muted mb-1">New Attachments to Upload ({{ files.length }}):</div>
                <div
                  v-for="(file, idx) in files"
                  :key="idx"
                  class="file-preview-card"
                >
                  <div class="file-thumb">
                    <img
                      v-if="file.previewUrl"
                      :src="file.previewUrl"
                      alt="Preview"
                      class="thumb-img"
                    />
                    <Video v-else-if="file.type?.startsWith('video/')" :size="22" class="text-primary" />
                    <FileText v-else :size="22" class="text-primary" />
                  </div>

                  <div class="file-info truncate">
                    <span class="bold small truncate">{{ file.name }}</span>
                    <span class="small muted">{{ formatBytes(file.size) }}</span>
                  </div>

                  <button
                    type="button"
                    class="btn btn-ghost btn-icon btn-sm text-red"
                    @click.stop="removeFile(idx)"
                    aria-label="Remove file"
                  >
                    <X :size="16" />
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Sidebar Options (Right) -->
          <div class="create-side-col stack">
            <!-- Category Selector -->
            <div class="card card-pad field">
              <label class="label">Content Category <span class="text-red">*</span></label>
              <p class="small text-2 mb-2">Select the category that best represents your content:</p>

              <div class="categories-selector">
                <label
                  v-for="cat in categories"
                  :key="cat.id"
                  class="category-option"
                  :class="{ selected: Number(form.category_id) === Number(cat.id) }"
                  @click="form.category_id = Number(cat.id)"
                >
                  <input
                    type="radio"
                    name="category"
                    :value="Number(cat.id)"
                    v-model="form.category_id"
                    required
                    class="sr-only"
                  />
                  <div class="category-option-icon" :style="{ background: `${cat.color}15`, color: cat.color }">
                    <component :is="getIcon(cat.icon)" :size="18" />
                  </div>
                  <div class="category-option-text">
                    <span class="bold small">{{ cat.name }}</span>
                    <span class="muted small truncate-2">{{ cat.description }}</span>
                  </div>
                </label>
              </div>
            </div>

            <!-- Community Optional -->
            <div class="card card-pad field">
              <label class="label">Related Community (Optional)</label>
              <select v-model="form.community_id" class="select">
                <option :value="null">-- General (No Specific Community) --</option>
                <option v-for="c in communities" :key="c.id" :value="Number(c.id)">
                  {{ c.name }}
                </option>
              </select>
              <span class="hint">Connect this post with an employee community discussion space</span>
            </div>

            <!-- Tags Input -->
            <div class="card card-pad field">
              <label class="label">Tags / Keywords</label>
              <div class="tags-input-wrapper">
                <input
                  v-model="tagInput"
                  @keydown.enter.prevent="addTag"
                  @keydown.,.prevent="addTag"
                  @blur="addTag"
                  type="text"
                  placeholder="Type tag and press Enter or comma..."
                  class="input"
                />
              </div>
              <div class="chips mt-2">
                <span
                  v-for="(tag, i) in form.tags"
                  :key="i"
                  class="badge badge-gray"
                >
                  #{{ tag }}
                  <button type="button" @click="removeTag(i)" class="tag-remove-btn">&times;</button>
                </span>
              </div>
              <span class="hint">e.g. #sop, #cashier, #operations, #innovation</span>
            </div>

            <!-- Action Buttons -->
            <div class="card card-pad action-card stack">
              <button
                type="submit"
                class="btn btn-primary btn-lg btn-block"
                :disabled="submitting"
              >
                <span v-if="submitting" class="spinner"></span>
                <Send v-else :size="18" />
                <span>
                  {{ editStatus === 'rejected' ? 'Resubmit for Review' : 'Submit for Review' }}
                </span>
              </button>

              <button
                type="button"
                class="btn btn-outline btn-block"
                :disabled="submitting"
                @click="handleSubmit('draft')"
              >
                <Bookmark :size="16" />
                <span>Update Draft</span>
              </button>

              <p class="small muted text-center mt-1">
                Your changes will be saved to your contribution workspace.
              </p>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import {
  ArrowLeft, UploadCloud, X, Send, Bookmark, Video, FileText,
  Award, Lightbulb, BookOpen, Rocket, Trophy, MessagesSquare,
  Camera, Building, Leaf, Crown, Users, AlertCircle
} from 'lucide-vue-next'
import api from '@/api/client'
import { useToastStore } from '@/stores/toast'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const toastStore = useToastStore()
const authStore = useAuthStore()

// Identify content ID from route params (/contributions/:id/edit) or query fallback (?edit=:id)
const contentId = computed(() => route.params.id || route.query.edit)

const loading = ref(true)
const loadError = ref(null)
const submitting = ref(false)
const isDragging = ref(false)

const editStatus = ref('')
const reviewNote = ref('')
const existingMedia = ref([])
const removedMediaIds = ref([])

const categories = ref([])
const communities = ref([])
const files = ref([])
const tagInput = ref('')

const form = ref({
  title: '',
  body: '',
  category_id: null,
  community_id: null,
  tags: [],
})

const totalMediaCount = computed(() => {
  return existingMedia.value.length + files.value.length
})

const iconMap = {
  Award, Lightbulb, BookOpen, Rocket, FileText, Trophy,
  MessagesSquare, Camera, Building, Leaf, Crown, Users,
}
const getIcon = (name) => iconMap[name] || BookOpen

const fetchMeta = async () => {
  try {
    const [catRes, comRes] = await Promise.all([
      api.get('/categories'),
      api.get('/communities'),
    ])
    categories.value = catRes.data
    communities.value = comRes.data
  } catch (err) {
    console.error('Failed to load categories/communities', err)
  }
}

const loadDraft = async () => {
  if (!contentId.value) {
    loadError.value = 'Content ID was not specified.'
    loading.value = false
    return
  }

  loading.value = true
  loadError.value = null

  try {
    const [res] = await Promise.all([
      api.get(`/contents/${contentId.value}`),
      fetchMeta(),
    ])

    const c = res.data.content
    if (!c) {
      loadError.value = 'Content was not found in the database.'
      return
    }

    if (c.status === 'published') {
      loadError.value = 'Konten yang sudah dipublikasikan tidak dapat diubah lagi.'
      return
    }

    form.value.title = c.title || ''
    form.value.body = c.body || ''
    const rawCatId = c.category_id ?? c.category?.id ?? null
    form.value.category_id = rawCatId !== null && rawCatId !== undefined ? Number(rawCatId) : (categories.value[0]?.id ? Number(categories.value[0].id) : null)
    const rawComId = c.community_id ?? c.community?.id ?? null
    form.value.community_id = rawComId !== null && rawComId !== undefined ? Number(rawComId) : null
    form.value.tags = parseLoadedTags(c.tags)
    existingMedia.value = c.media || []
    editStatus.value = c.status
    reviewNote.value = c.review_note || ''
  } catch (err) {
    console.error('Failed to load draft content', err)
    loadError.value = err.response?.data?.message || 'Gagal memuat konten draft. Pastikan Anda memiliki akses.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadDraft()
})

const removeExistingMedia = (id) => {
  removedMediaIds.value.push(id)
  existingMedia.value = existingMedia.value.filter((m) => m.id !== id)
}

const handleFileSelect = (e) => {
  addFiles(Array.from(e.target.files))
}

const handleDrop = (e) => {
  isDragging.value = false
  if (e.dataTransfer.files) {
    addFiles(Array.from(e.dataTransfer.files))
  }
}

const addFiles = (selectedFiles) => {
  for (const file of selectedFiles) {
    if (file.size > 52428800) {
      toastStore.error(`File ${file.name} exceeds the 50 MB limit.`)
      continue
    }
    const item = {
      raw: file,
      name: file.name,
      size: file.size,
      type: file.type,
      previewUrl: file.type.startsWith('image/') ? URL.createObjectURL(file) : null,
    }
    files.value.push(item)
  }
}

const removeFile = (idx) => {
  if (files.value[idx]?.previewUrl) {
    URL.revokeObjectURL(files.value[idx].previewUrl)
  }
  files.value.splice(idx, 1)
}

const parseLoadedTags = (raw) => {
  if (!raw) return []
  if (Array.isArray(raw)) {
    return raw.map((t) => String(t).trim().replace(/^#/, '')).filter(Boolean)
  }
  if (typeof raw === 'string') {
    try {
      const parsed = JSON.parse(raw)
      if (Array.isArray(parsed)) {
        return parsed.map((t) => String(t).trim().replace(/^#/, '')).filter(Boolean)
      }
    } catch (e) {
      // not json, continue
    }
    const cleaned = raw.replace(/^\{|\}$/g, '').replace(/"/g, '')
    return cleaned.split(/[, ]+/).map((t) => t.trim().replace(/^#/, '')).filter(Boolean)
  }
  return []
}

const addTag = () => {
  if (!tagInput.value) return
  const items = tagInput.value.split(/[, ]+/)
  items.forEach((item) => {
    const val = item.trim().replace(/^#/, '')
    if (val && !form.value.tags.includes(val)) {
      form.value.tags.push(val)
    }
  })
  tagInput.value = ''
}

const removeTag = (i) => {
  form.value.tags.splice(i, 1)
}

const handleSubmit = async (actionType) => {
  if (tagInput.value && tagInput.value.trim()) {
    addTag()
  }

  if (!form.value.title || !form.value.body || !form.value.category_id) {
    toastStore.error('Please fill in the title, category, and content body.')
    return
  }

  submitting.value = true
  const formData = new FormData()
  formData.append('title', form.value.title)
  formData.append('body', form.value.body)
  formData.append('category_id', form.value.category_id)
  if (form.value.community_id) formData.append('community_id', form.value.community_id)
  formData.append('action', actionType)
  formData.append('tags', JSON.stringify(form.value.tags))

  // Append new media files
  files.value.forEach((f) => {
    formData.append('media[]', f.raw)
  })

  // Append removed media IDs
  removedMediaIds.value.forEach((id) => {
    formData.append('remove_media[]', id)
  })

  try {
    const res = await api.post(`/contents/${contentId.value}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    if (actionType === 'submit') {
      const earned = Number(res.data.points_earned) || 0
      const baseMsg = editStatus.value === 'rejected'
        ? 'Konten berhasil diperbaiki dan diajukan ulang untuk review!'
        : 'Konten berhasil diajukan dan sedang menunggu review tim!'
      if (earned > 0) {
        toastStore.pointsEarned(earned, `${baseMsg} (+${earned} Poin)`)
        authStore.mutatePoints(earned)
      } else {
        toastStore.success(baseMsg)
        authStore.fetchMe()
      }
      router.push('/contributions?status=pending')
    } else {
      toastStore.success('Draft berhasil diperbarui.')
      router.push('/contributions?status=draft')
    }
  } catch (err) {
    const msg = err.response?.data?.message || 'Failed to save content.'
    toastStore.error(msg)
  } finally {
    submitting.value = false
  }
}

const formatBytes = (bytes) => {
  if (!bytes) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return `${parseFloat((bytes / Math.pow(k, i)).toFixed(1))} ${sizes[i]}`
}
</script>

<style scoped src="@/styles/features/create-content.css"></style>
