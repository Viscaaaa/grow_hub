<template>
  <div class="page admin-point-rules-page">
    <!-- Header with Breadcrumb Badge & Batch Action Buttons -->
    <div class="page-header">
      <div>
        <div class="header-badge mb-1">
          <ShieldCheck :size="14" />
          <span>Administration Console</span>
        </div>
        <h1 class="page-title">Gamification & Point Settings</h1>
        <p class="page-subtitle">
          Konfigurasi besaran perolehan poin per tindakan, batasan harian, dan aktifkan atau nonaktifkan sistem poin sesuai kebutuhan kebijakan perusahaan.
        </p>
      </div>

      <div class="header-actions">
        <button
          type="button"
          class="btn btn-outline"
          @click="isResetModalOpen = true"
          :disabled="loading || saving"
        >
          <RotateCcw :size="16" />
          <span>Reset Default</span>
        </button>

        <button
          type="button"
          class="btn btn-outline btn-disable-all"
          @click="isDisableAllModalOpen = true"
          :disabled="loading || saving || stats.active_rules === 0"
          title="Nonaktifkan seluruh aturan poin sekaligus"
        >
          <Ban :size="16" class="text-amber" />
          <span>Nonaktifkan Semua Rule</span>
        </button>

        <button
          type="button"
          class="btn btn-outline btn-enable-all"
          @click="isEnableAllModalOpen = true"
          :disabled="loading || saving || stats.inactive_rules === 0"
          title="Aktifkan seluruh aturan poin sekaligus"
        >
          <CheckCheck :size="16" class="text-primary" />
          <span>Aktifkan Semua Rule</span>
        </button>

        <button
          type="button"
          class="btn btn-primary"
          @click="saveAllRules"
          :disabled="loading || saving"
        >
          <Check :size="16" />
          <span>{{ saving ? 'Menyimpan...' : 'Simpan Semua Perubahan' }}</span>
        </button>
      </div>
    </div>

    <!-- Stats Overview Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon-wrapper bg-green-soft">
          <Sparkles :size="20" class="text-primary" />
        </div>
        <div class="stat-meta">
          <span class="stat-label">Total Aturan Poin</span>
          <span class="stat-val">{{ stats.total_rules || 0 }}</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon-wrapper bg-blue-soft">
          <Sliders :size="20" class="text-blue" />
        </div>
        <div class="stat-meta">
          <span class="stat-label">Sistem Poin Aktif</span>
          <span class="stat-val text-primary">{{ stats.active_rules || 0 }}</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon-wrapper bg-amber-soft">
          <Flame :size="20" class="text-amber" />
        </div>
        <div class="stat-meta">
          <span class="stat-label">Dinonaktifkan (0 Pts)</span>
          <span class="stat-val text-muted">{{ stats.inactive_rules || 0 }}</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon-wrapper bg-purple-soft">
          <Trophy :size="20" class="text-purple" />
        </div>
        <div class="stat-meta">
          <span class="stat-label">Poin Diberikan (All-Time)</span>
          <span class="stat-val">{{ formatNumber(stats.total_points_distributed || 0) }} Pts</span>
        </div>
      </div>
    </div>

    <!-- Filter & Search Controls Card -->
    <div class="controls-card">
      <div class="controls-row">
        <!-- Category Filter Tabs -->
        <div class="category-tabs">
          <button
            type="button"
            class="cat-tab-btn"
            :class="{ active: selectedCategory === 'all' }"
            @click="selectedCategory = 'all'"
          >
            <span>Semua Kategori</span>
          </button>
          <button
            type="button"
            class="cat-tab-btn"
            :class="{ active: selectedCategory === 'content' }"
            @click="selectedCategory = 'content'"
          >
            <BookOpen :size="14" />
            <span>Konten & Knowledge</span>
          </button>
          <button
            type="button"
            class="cat-tab-btn"
            :class="{ active: selectedCategory === 'social' }"
            @click="selectedCategory = 'social'"
          >
            <Heart :size="14" />
            <span>Social Engagement</span>
          </button>
          <button
            type="button"
            class="cat-tab-btn"
            :class="{ active: selectedCategory === 'learning' }"
            @click="selectedCategory = 'learning'"
          >
            <GraduationCap :size="14" />
            <span>Learning Center</span>
          </button>
          <button
            type="button"
            class="cat-tab-btn"
            :class="{ active: selectedCategory === 'general' }"
            @click="selectedCategory = 'general'"
          >
            <Sparkles :size="14" />
            <span>Daily / General</span>
          </button>
          <button
            type="button"
            class="cat-tab-btn"
            :class="{ active: selectedCategory === 'review' }"
            @click="selectedCategory = 'review'"
          >
            <ShieldCheck :size="14" />
            <span>Reviewer</span>
          </button>
        </div>

        <!-- Search and Status Filter Inputs -->
        <div class="filter-inputs-group">
          <div class="search-input-wrapper">
            <Search :size="15" class="search-icon-inside" />
            <input
              type="text"
              v-model="searchQuery"
              placeholder="Cari tindakan atau kode aksi..."
              class="input search-field"
            />
          </div>

          <select v-model="selectedStatus" class="select" style="min-width: 140px;">
            <option value="all">Semua Status</option>
            <option value="active">Hanya Aktif</option>
            <option value="inactive">Hanya Nonaktif</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Loading Skeleton State -->
    <div v-if="loading" class="rules-grid">
      <div v-for="i in 4" :key="i" class="rule-card">
        <div class="skeleton" style="height: 24px; width: 40%; margin-bottom: 8px;"></div>
        <div class="skeleton" style="height: 16px; width: 70%; margin-bottom: 16px;"></div>
        <div class="skeleton" style="height: 40px; width: 100%;"></div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredRules.length === 0" class="card card-pad text-center py-5">
      <Sliders :size="48" class="text-muted mb-2" style="margin: 0 auto;" />
      <h3 class="bold text-dark mb-1">Tidak Ada Aturan Ditemukan</h3>
      <p class="small text-muted">Coba ubah kata kunci pencarian atau filter status yang dipilih.</p>
    </div>

    <!-- Rules List Grid -->
    <div v-else class="rules-grid">
      <div
        v-for="rule in filteredRules"
        :key="rule.id"
        class="rule-card"
        :class="{ 'inactive-rule': !rule.is_active }"
      >
        <!-- Top Row: Icon, Title, Description, and Toggle Switch -->
        <div class="rule-top-row">
          <div class="rule-title-group">
            <div
              class="rule-category-icon"
              :class="getCategoryIconClass(rule.category)"
            >
              <component :is="getCategoryIcon(rule.action, rule.category)" :size="20" />
            </div>

            <div>
              <div class="rule-title">
                <span>{{ rule.name }}</span>
                <span class="action-code-tag">{{ rule.action }}</span>
              </div>
              <p class="rule-description">{{ rule.description }}</p>
            </div>
          </div>

          <!-- Switch Toggle Button -->
          <div class="toggle-control-box">
            <span
              class="toggle-status-text"
              :class="rule.is_active ? 'status-active' : 'status-inactive'"
            >
              {{ rule.is_active ? 'Aktif' : 'Nonaktif' }}
            </span>
            <label class="switch">
              <input
                type="checkbox"
                v-model="rule.is_active"
                @change="onToggleSwitch(rule)"
              />
              <span class="slider"></span>
            </label>
          </div>
        </div>

        <!-- Bottom Configuration Row: Point Value, Daily Cap, and Quick Save Button -->
        <div class="rule-config-row">
          <div class="config-inputs-group">
            <!-- Points Input with Steppers -->
            <div class="config-field">
              <span class="config-field-label">Poin Diberikan:</span>
              <div class="point-input-wrapper">
                <button
                  type="button"
                  class="point-step-btn"
                  @click="adjustPoints(rule, -1)"
                  :disabled="!rule.is_active"
                >
                  −
                </button>
                <input
                  type="number"
                  v-model.number="rule.points"
                  min="0"
                  max="1000"
                  class="point-number-input"
                  :disabled="!rule.is_active"
                />
                <button
                  type="button"
                  class="point-step-btn"
                  @click="adjustPoints(rule, 1)"
                  :disabled="!rule.is_active"
                >
                  +
                </button>
              </div>
              <span class="small bold" :class="rule.is_active ? 'text-primary' : 'text-muted'">
                Pts
              </span>
            </div>

            <!-- Daily Cap Input -->
            <div class="config-field">
              <span class="config-field-label">Batas Per Hari:</span>
              <div class="daily-cap-input-wrapper">
                <input
                  v-if="rule.has_limit"
                  type="number"
                  v-model.number="rule.daily_cap"
                  min="1"
                  max="100"
                  class="input cap-number-input"
                  :disabled="!rule.is_active"
                />
                <span v-else class="cap-unlimited-badge">
                  Tanpa Batas
                </span>

                <button
                  type="button"
                  class="btn btn-ghost btn-xs text-muted"
                  @click="toggleDailyCapLimit(rule)"
                  :disabled="!rule.is_active"
                >
                  {{ rule.has_limit ? 'Hapus Batas' : 'Atur Batas' }}
                </button>
              </div>
            </div>
          </div>

          <!-- Save Button per Card -->
          <div class="rule-action-buttons">
            <button
              type="button"
              class="btn btn-outline btn-save-rule"
              @click="saveSingleRule(rule)"
              :disabled="saving"
            >
              <Check :size="14" />
              <span>Simpan Aturan</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Reset to Defaults Confirmation Modal -->
    <div v-if="isResetModalOpen" class="modal-overlay" @click.self="isResetModalOpen = false">
      <div class="modal-content-card">
        <div class="modal-icon-header bg-amber-soft">
          <AlertTriangle :size="26" class="text-amber" />
        </div>
        <h3 class="modal-title">Reset Aturan Poin ke Nilai Default?</h3>
        <p class="modal-body-text">
          Tindakan ini akan mengembalikan semua besaran poin, batasan harian, dan status aktivasi sistem poin ke konfigurasi awal bawaan sistem. Apakah Anda ingin melanjutkan?
        </p>

        <div class="modal-actions">
          <button
            type="button"
            class="btn btn-outline"
            @click="isResetModalOpen = false"
            :disabled="saving"
          >
            Batal
          </button>
          <button
            type="button"
            class="btn btn-primary"
            @click="resetToDefaults"
            :disabled="saving"
          >
            <RotateCcw :size="16" />
            <span>{{ saving ? 'Mereset...' : 'Ya, Reset ke Default' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Disable All Rules Confirmation Modal -->
    <div v-if="isDisableAllModalOpen" class="modal-overlay" @click.self="isDisableAllModalOpen = false">
      <div class="modal-content-card">
        <div class="modal-icon-header bg-amber-soft">
          <Ban :size="26" class="text-amber" />
        </div>
        <h3 class="modal-title">Nonaktifkan Semua Aturan Poin?</h3>
        <p class="modal-body-text">
          Tindakan ini akan menonaktifkan seluruh aturan sistem poin gamifikasi (semua aturan diubah menjadi <strong>Nonaktif</strong>). Aktivitas pengguna seperti membuat konten, membaca artikel, login harian, komentar, share, atau menyelesaikan kursus tidak akan mendapatkan poin (0 Pts) sampai Anda mengaktifkannya kembali.
        </p>

        <div class="modal-actions">
          <button
            type="button"
            class="btn btn-outline"
            @click="isDisableAllModalOpen = false"
            :disabled="saving"
          >
            Batal
          </button>
          <button
            type="button"
            class="btn btn-primary"
            style="background-color: #d97706; border-color: #d97706;"
            @click="disableAllRules"
            :disabled="saving"
          >
            <Ban :size="16" />
            <span>{{ saving ? 'Menonaktifkan...' : 'Ya, Nonaktifkan Semua' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Enable All Rules Confirmation Modal -->
    <div v-if="isEnableAllModalOpen" class="modal-overlay" @click.self="isEnableAllModalOpen = false">
      <div class="modal-content-card">
        <div class="modal-icon-header bg-green-soft">
          <CheckCheck :size="26" class="text-primary" />
        </div>
        <h3 class="modal-title">Aktifkan Semua Aturan Poin?</h3>
        <p class="modal-body-text">
          Tindakan ini akan mengaktifkan seluruh aturan sistem poin gamifikasi (semua aturan diubah menjadi <strong>Aktif</strong>). Setiap aktivitas pengguna seperti membuat konten, membaca artikel, login harian, komentar, share, atau kursus akan kembali memberikan poin sesuai besaran yang ditetapkan.
        </p>

        <div class="modal-actions">
          <button
            type="button"
            class="btn btn-outline"
            @click="isEnableAllModalOpen = false"
            :disabled="saving"
          >
            Batal
          </button>
          <button
            type="button"
            class="btn btn-primary"
            @click="enableAllRules"
            :disabled="saving"
          >
            <CheckCheck :size="16" />
            <span>{{ saving ? 'Mengaktifkan...' : 'Ya, Aktifkan Semua' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api/client'
import { useToastStore } from '@/stores/toast'
import {
  Sparkles,
  Flame,
  Trophy,
  ShieldCheck,
  Check,
  CheckCheck,
  RotateCcw,
  Search,
  Sliders,
  BookOpen,
  Heart,
  Bookmark,
  Share2,
  GraduationCap,
  MessageSquare,
  Eye,
  Send,
  AlertTriangle,
  Ban,
} from 'lucide-vue-next'

const toastStore = useToastStore()

const rules = ref([])
const stats = ref({
  total_rules: 0,
  active_rules: 0,
  inactive_rules: 0,
  total_points_distributed: 0,
})

const loading = ref(true)
const saving = ref(false)
const searchQuery = ref('')
const selectedCategory = ref('all')
const selectedStatus = ref('all')
const isResetModalOpen = ref(false)
const isDisableAllModalOpen = ref(false)
const isEnableAllModalOpen = ref(false)

// Fetch all rules and stats
const fetchRules = async () => {
  loading.value = true
  try {
    const res = await api.get('/admin/point-rules')
    rules.value = (res.data.rules || []).map((r) => ({
      ...r,
      has_limit: r.daily_cap !== null && r.daily_cap > 0,
    }))
    stats.value = res.data.stats || stats.value
  } catch (err) {
    console.error('Failed to load point rules', err)
    toastStore.error('Gagal memuat daftar aturan sistem poin.')
  } finally {
    loading.value = false
  }
}

// Filtered rules computed property
const filteredRules = computed(() => {
  return rules.value.filter((r) => {
    // Category match
    if (selectedCategory.value !== 'all' && r.category !== selectedCategory.value) {
      return false
    }

    // Status match
    if (selectedStatus.value === 'active' && !r.is_active) {
      return false
    }
    if (selectedStatus.value === 'inactive' && r.is_active) {
      return false
    }

    // Search query match
    if (searchQuery.value.trim()) {
      const q = searchQuery.value.toLowerCase()
      const matchName = (r.name || '').toLowerCase().includes(q)
      const matchAction = (r.action || '').toLowerCase().includes(q)
      const matchDesc = (r.description || '').toLowerCase().includes(q)
      return matchName || matchAction || matchDesc
    }

    return true
  })
})

// Toggle rule on/off immediately via API
const onToggleSwitch = async (rule) => {
  try {
    const res = await api.post(`/admin/point-rules/${rule.id}/toggle`)
    rule.is_active = res.data.rule.is_active
    updateStatsActiveCount()
    toastStore.success(res.data.message)
  } catch (err) {
    console.error('Failed to toggle point rule', err)
    rule.is_active = !rule.is_active // rollback
    toastStore.error('Gagal mengubah status aktivasi sistem poin.')
  }
}

// Adjust point by delta (+1 / -1)
const adjustPoints = (rule, delta) => {
  const current = Number(rule.points) || 0
  rule.points = Math.max(0, current + delta)
}

// Toggle whether rule has daily cap
const toggleDailyCapLimit = (rule) => {
  rule.has_limit = !rule.has_limit
  if (rule.has_limit) {
    rule.daily_cap = rule.daily_cap || 10
  } else {
    rule.daily_cap = null
  }
}

// Save single rule
const saveSingleRule = async (rule) => {
  saving.value = true
  try {
    const payload = {
      points: Number(rule.points) || 0,
      daily_cap: rule.has_limit ? (Number(rule.daily_cap) || null) : null,
      is_active: rule.is_active,
    }
    const res = await api.put(`/admin/point-rules/${rule.id}`, payload)
    toastStore.success(res.data.message || `Aturan ${rule.name} berhasil disimpan.`)
    updateStatsActiveCount()
  } catch (err) {
    console.error('Failed to save rule', err)
    toastStore.error(err.response?.data?.message || 'Gagal menyimpan aturan poin.')
  } finally {
    saving.value = false
  }
}

// Batch save all rules
const saveAllRules = async () => {
  saving.value = true
  try {
    const payload = {
      rules: rules.value.map((r) => ({
        id: r.id,
        points: Number(r.points) || 0,
        daily_cap: r.has_limit ? (Number(r.daily_cap) || null) : null,
        is_active: r.is_active,
      })),
    }
    const res = await api.put('/admin/point-rules/batch', payload)
    toastStore.success(res.data.message || 'Semua aturan poin berhasil diperbarui.')
    updateStatsActiveCount()
  } catch (err) {
    console.error('Failed to batch save point rules', err)
    toastStore.error('Gagal menyimpan aturan poin.')
  } finally {
    saving.value = false
  }
}

// Reset to default
const resetToDefaults = async () => {
  saving.value = true
  try {
    const res = await api.post('/admin/point-rules/reset-defaults')
    toastStore.success(res.data.message)
    isResetModalOpen.value = false
    await fetchRules()
  } catch (err) {
    console.error('Failed to reset point rules', err)
    toastStore.error('Gagal mereset aturan poin.')
  } finally {
    saving.value = false
  }
}

// Disable all rules
const disableAllRules = async () => {
  saving.value = true
  try {
    const res = await api.post('/admin/point-rules/disable-all')
    toastStore.success(res.data.message)
    isDisableAllModalOpen.value = false
    await fetchRules()
  } catch (err) {
    console.error('Failed to disable all point rules', err)
    toastStore.error('Gagal menonaktifkan aturan poin.')
  } finally {
    saving.value = false
  }
}

// Enable all rules
const enableAllRules = async () => {
  saving.value = true
  try {
    const res = await api.post('/admin/point-rules/enable-all')
    toastStore.success(res.data.message)
    isEnableAllModalOpen.value = false
    await fetchRules()
  } catch (err) {
    console.error('Failed to enable all point rules', err)
    toastStore.error('Gagal mengaktifkan aturan poin.')
  } finally {
    saving.value = false
  }
}

// Helper to recalculate active/inactive stats locally
const updateStatsActiveCount = () => {
  const active = rules.value.filter((r) => r.is_active).length
  stats.value.active_rules = active
  stats.value.inactive_rules = rules.value.length - active
}

// Format numbers
const formatNumber = (num) => {
  return new Intl.NumberFormat('id-ID').format(num || 0)
}

// Icon mapper
const getCategoryIcon = (action, category) => {
  if (action === 'daily_login') return Sparkles
  if (action === 'content_submitted') return Send
  if (action === 'content_published') return BookOpen
  if (action === 'content_viewed') return Eye
  if (action === 'like_received') return Heart
  if (action === 'comment_given' || action === 'comment_received') return MessageSquare
  if (action === 'share_given' || action === 'share_received') return Share2
  if (action === 'save_received') return Bookmark
  if (action === 'review_done') return ShieldCheck
  if (action === 'course_completed') return GraduationCap
  return Sparkles
}

// Category badge color
const getCategoryIconClass = (category) => {
  switch (category) {
    case 'content':
      return 'cat-badge-content'
    case 'social':
      return 'cat-badge-social'
    case 'learning':
      return 'cat-badge-learning'
    case 'review':
      return 'cat-badge-review'
    default:
      return 'cat-badge-general'
  }
}

onMounted(() => {
  fetchRules()
})
</script>

<style scoped src="@/styles/features/admin-point-rules.css"></style>
