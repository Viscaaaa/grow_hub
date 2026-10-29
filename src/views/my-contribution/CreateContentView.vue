<template>
  <div class="page create-page">
    <div class="page-header">
      <div>
        <router-link to="/contributions" class="btn btn-ghost btn-sm mb-2" style="padding-left: 0;">
          <ArrowLeft :size="16" />
          <span>Back to My Contributions</span>
        </router-link>
        <h1 class="page-title">Share Knowledge & Experience</h1>
        <p class="page-subtitle">
          Write down your ideas, best practices, or field solutions. Submissions will be reviewed by the curation team before being published company-wide.
        </p>
      </div>
    </div>

    <!-- Info banner regarding Review & Points flow -->
    <div class="card card-pad bg-green-50 mb-4 flow-banner">
      <div class="flow-icon">
        <Sparkles :size="22" class="text-primary" />
      </div>
      <div class="flow-text">
        <span class="bold text-dark">GrowHub Content Curation Process:</span>
        <div class="flow-steps">
          <span>1. Tulis & Ajukan Konten (Dapatkan Poin)</span>
          <ChevronRight :size="14" class="muted" />
          <span>2. Validasi Tim Reviewer</span>
          <ChevronRight :size="14" class="muted" />
          <span>3. Publikasi ke Knowledge Feed</span>
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

          <!-- Media Upload Section (Images, Video, Documents) -->
          <div class="card card-pad field">
            <div class="row-between mb-2">
              <div>
                <label class="label">Attach Media (Photos, Videos, or Documents / SOP)</label>
                <p class="small text-2">Supports multi-file upload: JPG, PNG, MP4, PDF, DOCX, XLSX (max 50 MB/file)</p>
              </div>
              <span class="badge badge-gray">{{ files.length }} Files Selected</span>
            </div>

            <!-- Drag & Drop Zone -->
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
                <div class="bold">Click or drag media files here</div>
                <span class="small muted">Share store display photos, short video tutorials, or SOP guidelines</span>
              </div>
            </div>

            <!-- Attached Files Preview List -->
            <div v-if="files.length > 0" class="files-preview-list mt-3">
              <div
                v-for="(file, idx) in files"
                :key="idx"
                class="file-preview-card"
              >
                <!-- Thumbnail / Icon -->
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
              <option v-for="c in communities" :key="c.id" :value="c.id">
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
              <span>Submit for Review</span>
            </button>

            <button
              type="button"
              class="btn btn-outline btn-block"
              :disabled="submitting"
              @click="handleSubmit('draft')"
            >
              <Bookmark :size="16" />
              <span>Save as Draft</span>
            </button>

            <p class="small muted text-center mt-1">
              Your submission will be validated by the Reviewer Team before being published company-wide.
            </p>
          </div>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import {
  ArrowLeft, Sparkles, ChevronRight, UploadCloud, X, Send,
  Bookmark, Video, FileText, Award, Lightbulb, BookOpen,
  Rocket, Trophy, MessagesSquare, Camera, Building, Leaf, Crown, Users
} from 'lucide-vue-next'
import api from '@/api/client'
import { useToastStore } from '@/stores/toast'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const toastStore = useToastStore()
const authStore = useAuthStore()

// If old link with ?edit=:id was accessed, seamlessly forward to dedicated edit route
if (route.query.edit) {
  router.replace(`/contributions/${route.query.edit}/edit`)
}

const submitting = ref(false)
const isDragging = ref(false)
const categories = ref([])
const communities = ref([])
const files = ref([])
const tagInput = ref('')

const form = ref({
  title: '',
  body: '',
  category_id: null,
  community_id: null,
  tags: ['retail', 'best-practice'],
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
    if (categories.value.length > 0) {
      form.value.category_id = categories.value[0].id
    }
  } catch (err) {
    console.error('Failed to load categories/communities', err)
  }
}

onMounted(() => {
  fetchMeta()
})

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

  files.value.forEach((f) => {
    formData.append('media[]', f.raw)
  })

  try {
    const res = await api.post('/contents', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    if (actionType === 'submit') {
      const earned = Number(res.data.points_earned) || 0
      if (earned > 0) {
        toastStore.pointsEarned(earned, `Konten berhasil diajukan dan menunggu review (+${earned} Poin)!`)
        authStore.mutatePoints(earned)
      } else {
        toastStore.success('Konten berhasil diajukan dan sedang menunggu review tim.')
        authStore.fetchMe()
      }
      router.push('/contributions?status=pending')
    } else {
      toastStore.success('Draft berhasil disimpan.')
      router.push('/contributions?status=draft')
    }
  } catch (err) {
    const msg = err.response?.data?.message || 'Failed to submit content.'
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
