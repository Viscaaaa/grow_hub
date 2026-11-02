<template>
  <div class="page admin-level-rules-page">
    <!-- Header with Breadcrumb Badge & Actions -->
    <div class="page-header">
      <div>
        <div class="header-badge mb-1">
          <ShieldCheck :size="14" />
          <span>Administration Console</span>
        </div>
        <h1 class="page-title">Level System Settings</h1>
        <p class="page-subtitle">
          Atur jenjang gamifikasi karyawan: kelola nama level, urutan level (tier), poin minimal yang harus dicapai, dan visual badge untuk mendorong keaktifan tim.
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
          class="btn btn-outline"
          @click="openCreateModal"
          :disabled="loading || saving"
        >
          <Plus :size="16" />
          <span>Tambah Level Baru</span>
        </button>

        <button
          type="button"
          class="btn btn-primary"
          @click="saveAllLevels"
          :disabled="loading || saving || modifiedCount === 0"
        >
          <Check :size="16" />
          <span>{{ saving ? 'Menyimpan...' : `Simpan Semua Perubahan (${modifiedCount})` }}</span>
        </button>
      </div>
    </div>

    <!-- Stats Overview Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon-wrapper bg-green-soft">
          <Layers :size="20" class="text-primary" />
        </div>
        <div class="stat-meta">
          <span class="stat-label">Total Tingkatan Level</span>
          <span class="stat-val">{{ stats.total_levels || 0 }} Tier</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon-wrapper bg-blue-soft">
          <CheckCircle2 :size="20" class="text-blue" />
        </div>
        <div class="stat-meta">
          <span class="stat-label">Level Aktif di Sistem</span>
          <span class="stat-val text-primary">{{ stats.active_levels || 0 }} Aktif</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon-wrapper bg-purple-soft">
          <Sparkles :size="20" class="text-purple" />
        </div>
        <div class="stat-meta">
          <span class="stat-label">Poin Tier Tertinggi</span>
          <span class="stat-val">{{ formatNumber(stats.max_points || 0) }} Pts</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon-wrapper bg-amber-soft">
          <Users :size="20" class="text-amber" />
        </div>
        <div class="stat-meta">
          <span class="stat-label">Karyawan Terdistribusi</span>
          <span class="stat-val">{{ stats.total_users || 0 }} Anggota</span>
        </div>
      </div>
    </div>

    <!-- Visual Level Progression Roadmap / Ladder Preview -->
    <div class="roadmap-card">
      <div class="roadmap-header">
        <div class="roadmap-title-box">
          <TrendingUp :size="18" class="text-primary" />
          <div>
            <h3 class="roadmap-title">Level Progression Roadmap</h3>
            <p class="roadmap-subtitle">Visualisasi alur tangga kenaikan level berdasarkan akumulasi poin karyawan</p>
          </div>
        </div>
      </div>

      <div class="roadmap-scroll-wrap">
        <div class="roadmap-track">
          <template v-for="(lvl, idx) in roadmapLevels" :key="lvl.id">
            <div class="roadmap-node-wrap">
              <div class="roadmap-node" @click="openEditModal(lvl)" :title="`Klik untuk edit Level ${lvl.level}: ${lvl.name}`">
                <div class="roadmap-badge-circle" :style="{ backgroundColor: lvl.badge_color || '#10b981' }">
                  <component :is="getIconComponent(lvl.badge_icon)" :size="24" />
                  <span class="roadmap-level-pill">Lv.{{ lvl.level }}</span>
                </div>
                <span class="roadmap-node-name">{{ lvl.name }}</span>
                <span class="roadmap-node-pts">{{ formatNumber(lvl.min_points) }} Pts</span>
                <span class="roadmap-node-users">{{ lvl.user_count || 0 }} Karyawan</span>
              </div>

              <!-- Connector Line -->
              <div
                v-if="idx < roadmapLevels.length - 1"
                class="roadmap-connector roadmap-connector-active"
              ></div>
            </div>
          </template>
        </div>
      </div>
    </div>

    <!-- Content Card: Table & Batch Editor -->
    <div class="content-card">
      <!-- Toolbar -->
      <div class="table-toolbar">
        <div class="toolbar-left">
          <div class="search-box">
            <Search :size="16" class="text-muted" />
            <input
              type="text"
              v-model="searchQuery"
              placeholder="Cari nama level atau tingkatan..."
            />
          </div>

          <div class="filter-tab-group">
            <button
              type="button"
              class="filter-tab-btn"
              :class="{ active: filterStatus === 'all' }"
              @click="filterStatus = 'all'"
            >
              Semua ({{ levels.length }})
            </button>
            <button
              type="button"
              class="filter-tab-btn"
              :class="{ active: filterStatus === 'active' }"
              @click="filterStatus = 'active'"
            >
              Aktif ({{ activeCount }})
            </button>
            <button
              type="button"
              class="filter-tab-btn"
              :class="{ active: filterStatus === 'inactive' }"
              @click="filterStatus = 'inactive'"
            >
              Non-aktif ({{ levels.length - activeCount }})
            </button>
          </div>
        </div>

        <div class="toolbar-right">
          <span v-if="modifiedCount > 0" class="unsaved-badge">
            <AlertTriangle :size="14" />
            <span>{{ modifiedCount }} perubahan belum disimpan</span>
          </span>
        </div>
      </div>

      <!-- Table View -->
      <div class="table-responsive">
        <table class="level-table">
          <thead>
            <tr>
              <th style="width: 80px;">Urutan</th>
              <th style="min-width: 240px;">Nama Level</th>
              <th style="width: 160px;">Poin Minimal</th>
              <th style="width: 170px;">Rentang Tier</th>
              <th style="width: 180px;">Distribusi User</th>
              <th style="width: 100px; text-align: center;">Status</th>
              <th style="width: 110px; text-align: right;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="7" style="text-align: center; padding: 40px; color: #6b7280;">
                Memuat data tingkatan level...
              </td>
            </tr>
            <tr v-else-if="filteredLevels.length === 0">
              <td colspan="7" style="text-align: center; padding: 40px; color: #6b7280;">
                Tidak ada tingkatan level yang sesuai pencarian.
              </td>
            </tr>
            <tr
              v-else
              v-for="lvl in filteredLevels"
              :key="lvl.id"
              :class="{ 'level-row-inactive': !lvl.is_active }"
            >
              <!-- Urutan Level (Order) -->
              <td>
                <div class="tier-order-badge" :title="`Urutan Tier ${lvl.level}`">
                  {{ lvl.level }}
                </div>
              </td>

              <!-- Nama Level + Badge -->
              <td>
                <div class="level-name-col">
                  <div
                    class="level-badge-pill"
                    :style="{ backgroundColor: lvl.badge_color || '#10b981' }"
                    :title="`Badge: ${lvl.badge_icon || 'award'}`"
                  >
                    <component :is="getIconComponent(lvl.badge_icon)" :size="18" />
                  </div>
                  <div class="level-name-meta">
                    <input
                      type="text"
                      class="level-name-input"
                      v-model="lvl.name"
                      placeholder="Nama level..."
                    />
                    <span class="level-desc-sub" :title="lvl.description || 'Tidak ada deskripsi'">
                      {{ lvl.description || 'Tidak ada deskripsi' }}
                    </span>
                  </div>
                </div>
              </td>

              <!-- Poin Minimal Input -->
              <td>
                <div class="points-input-wrap">
                  <input
                    type="number"
                    min="0"
                    step="50"
                    class="points-input-field"
                    v-model.number="lvl.min_points"
                  />
                  <span class="points-suffix">Pts</span>
                </div>
              </td>

              <!-- Rentang Tier (Tier Range) -->
              <td>
                <span class="tier-range-pill">
                  {{ getTierRangeLabel(lvl) }}
                </span>
              </td>

              <!-- Distribusi Karyawan -->
              <td>
                <div class="user-dist-wrap">
                  <div class="user-dist-meta">
                    <span class="bold">{{ lvl.user_count || 0 }} Karyawan</span>
                    <span class="text-muted">{{ getUserPercentage(lvl.user_count) }}%</span>
                  </div>
                  <div class="dist-bar-track">
                    <div
                      class="dist-bar-fill"
                      :style="{
                        width: `${getUserPercentage(lvl.user_count)}%`,
                        backgroundColor: lvl.badge_color || '#10b981'
                      }"
                    ></div>
                  </div>
                </div>
              </td>

              <!-- Status Toggle Switch -->
              <td style="text-align: center;">
                <label class="switch-label">
                  <input
                    type="checkbox"
                    v-model="lvl.is_active"
                    @change="handleInlineToggle(lvl)"
                  />
                  <span class="slider-round"></span>
                </label>
              </td>

              <!-- Aksi -->
              <td style="text-align: right;">
                <div class="row-actions" style="justify-content: flex-end;">
                  <button
                    type="button"
                    class="action-icon-btn"
                    title="Edit Detail Level"
                    @click="openEditModal(lvl)"
                  >
                    <Pencil :size="15" />
                  </button>
                  <button
                    type="button"
                    class="action-icon-btn danger"
                    title="Hapus Level"
                    :disabled="levels.length <= 1"
                    @click="openDeleteModal(lvl)"
                  >
                    <Trash2 :size="15" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal: Tambah Level Baru -->
    <div v-if="isCreateModalOpen" class="modal-overlay" @click.self="isCreateModalOpen = false">
      <div class="modal-card">
        <div class="modal-header">
          <div class="modal-title-box">
            <Plus :size="18" class="text-primary" />
            <h4 class="modal-title">Tambah Tingkatan Level Baru</h4>
          </div>
          <button type="button" class="close-btn" @click="isCreateModalOpen = false">
            <X :size="18" />
          </button>
        </div>

        <div class="modal-body">
          <div class="form-row-2">
            <div class="form-group">
              <label class="form-label">Urutan Level (Tier)</label>
              <input
                type="number"
                min="1"
                max="100"
                class="form-input"
                v-model.number="createForm.level"
                placeholder="Contoh: 9"
                required
              />
            </div>
            <div class="form-group">
              <label class="form-label">Poin Minimal (Pts)</label>
              <input
                type="number"
                min="0"
                class="form-input"
                v-model.number="createForm.min_points"
                placeholder="Contoh: 4000"
                required
              />
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Nama Level</label>
            <input
              type="text"
              class="form-input"
              v-model="createForm.name"
              placeholder="Contoh: Grand Master, Retail Legend, etc."
              required
            />
          </div>

          <div class="form-group">
            <label class="form-label">Pilih Warna Badge</label>
            <div class="color-swatches">
              <button
                v-for="color in availableColors"
                :key="color.hex"
                type="button"
                class="color-swatch-btn"
                :class="{ active: createForm.badge_color === color.hex }"
                :style="{ backgroundColor: color.hex }"
                :title="color.name"
                @click="createForm.badge_color = color.hex"
              >
                <Check v-if="createForm.badge_color === color.hex" :size="16" />
              </button>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Pilih Ikon Badge</label>
            <div class="icon-swatches">
              <button
                v-for="iconItem in availableIcons"
                :key="iconItem.key"
                type="button"
                class="icon-swatch-btn"
                :class="{ active: createForm.badge_icon === iconItem.key }"
                :title="iconItem.name"
                @click="createForm.badge_icon = iconItem.key"
              >
                <component :is="iconItem.component" :size="20" />
              </button>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Deskripsi / Filosofi Level</label>
            <textarea
              class="form-input"
              rows="2"
              v-model="createForm.description"
              placeholder="Deskripsi singkat pencapaian pada tingkatan level ini..."
            ></textarea>
          </div>

          <!-- Preview Badge -->
          <div class="preview-badge-box">
            <div
              class="preview-badge-circle"
              :style="{ backgroundColor: createForm.badge_color }"
            >
              <component :is="getIconComponent(createForm.badge_icon)" :size="22" />
            </div>
            <div class="preview-badge-text">
              <h5>Level {{ createForm.level || 1 }} – {{ createForm.name || 'Nama Level' }}</h5>
              <p>Mulai dari {{ formatNumber(createForm.min_points || 0) }} Poin</p>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline" @click="isCreateModalOpen = false">
            Batal
          </button>
          <button
            type="button"
            class="btn btn-primary"
            :disabled="submitting || !createForm.name"
            @click="submitCreate"
          >
            {{ submitting ? 'Menambahkan...' : 'Tambah Level' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Edit Detail Level -->
    <div v-if="isEditModalOpen" class="modal-overlay" @click.self="isEditModalOpen = false">
      <div class="modal-card">
        <div class="modal-header">
          <div class="modal-title-box">
            <Pencil :size="18" class="text-primary" />
            <h4 class="modal-title">Edit Detail Level</h4>
          </div>
          <button type="button" class="close-btn" @click="isEditModalOpen = false">
            <X :size="18" />
          </button>
        </div>

        <div class="modal-body">
          <div class="form-row-2">
            <div class="form-group">
              <label class="form-label">Urutan Level (Tier)</label>
              <input
                type="number"
                min="1"
                max="100"
                class="form-input"
                v-model.number="editForm.level"
                required
              />
            </div>
            <div class="form-group">
              <label class="form-label">Poin Minimal (Pts)</label>
              <input
                type="number"
                min="0"
                class="form-input"
                v-model.number="editForm.min_points"
                required
              />
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Nama Level</label>
            <input
              type="text"
              class="form-input"
              v-model="editForm.name"
              required
            />
          </div>

          <div class="form-group">
            <label class="form-label">Pilih Warna Badge</label>
            <div class="color-swatches">
              <button
                v-for="color in availableColors"
                :key="color.hex"
                type="button"
                class="color-swatch-btn"
                :class="{ active: editForm.badge_color === color.hex }"
                :style="{ backgroundColor: color.hex }"
                :title="color.name"
                @click="editForm.badge_color = color.hex"
              >
                <Check v-if="editForm.badge_color === color.hex" :size="16" />
              </button>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Pilih Ikon Badge</label>
            <div class="icon-swatches">
              <button
                v-for="iconItem in availableIcons"
                :key="iconItem.key"
                type="button"
                class="icon-swatch-btn"
                :class="{ active: editForm.badge_icon === iconItem.key }"
                :title="iconItem.name"
                @click="editForm.badge_icon = iconItem.key"
              >
                <component :is="iconItem.component" :size="20" />
              </button>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Deskripsi / Filosofi Level</label>
            <textarea
              class="form-input"
              rows="2"
              v-model="editForm.description"
            ></textarea>
          </div>

          <!-- Preview Badge -->
          <div class="preview-badge-box">
            <div
              class="preview-badge-circle"
              :style="{ backgroundColor: editForm.badge_color }"
            >
              <component :is="getIconComponent(editForm.badge_icon)" :size="22" />
            </div>
            <div class="preview-badge-text">
              <h5>Level {{ editForm.level }} – {{ editForm.name }}</h5>
              <p>Mulai dari {{ formatNumber(editForm.min_points) }} Poin</p>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline" @click="isEditModalOpen = false">
            Batal
          </button>
          <button
            type="button"
            class="btn btn-primary"
            :disabled="submitting || !editForm.name"
            @click="submitEdit"
          >
            {{ submitting ? 'Menyimpan...' : 'Simpan Perubahan' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Konfirmasi Hapus Level -->
    <div v-if="isDeleteModalOpen" class="modal-overlay" @click.self="isDeleteModalOpen = false">
      <div class="modal-card">
        <div class="modal-header">
          <div class="modal-title-box">
            <AlertTriangle :size="18" class="text-amber" />
            <h4 class="modal-title">Hapus Tingkatan Level</h4>
          </div>
          <button type="button" class="close-btn" @click="isDeleteModalOpen = false">
            <X :size="18" />
          </button>
        </div>

        <div class="modal-body">
          <p style="margin-top: 0; color: #374151; font-size: 14px;">
            Apakah Anda yakin ingin menghapus level <strong>Level {{ selectedLevel?.level }} – {{ selectedLevel?.name }}</strong>?
          </p>
          <div style="background: #fef2f2; border: 1px solid #fecaca; padding: 12px 14px; border-radius: 8px; color: #b91c1c; font-size: 12.5px;">
            Perhitungan level karyawan yang berada pada tingkatan ini akan otomatis disesuaikan ke tingkatan level terdekat berdasarkan total poin mereka.
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline" @click="isDeleteModalOpen = false">
            Batal
          </button>
          <button
            type="button"
            class="btn btn-outline danger"
            :disabled="submitting"
            @click="submitDelete"
          >
            {{ submitting ? 'Menghapus...' : 'Ya, Hapus Level' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Konfirmasi Reset Default -->
    <div v-if="isResetModalOpen" class="modal-overlay" @click.self="isResetModalOpen = false">
      <div class="modal-card">
        <div class="modal-header">
          <div class="modal-title-box">
            <RotateCcw :size="18" class="text-primary" />
            <h4 class="modal-title">Reset Konfigurasi Level Bawaan</h4>
          </div>
          <button type="button" class="close-btn" @click="isResetModalOpen = false">
            <X :size="18" />
          </button>
        </div>

        <div class="modal-body">
          <p style="margin-top: 0; color: #374151; font-size: 14px;">
            Tindakan ini akan mengembalikan konfigurasi seluruh tingkatan level ke nilai bawaan standar sistem:
          </p>
          <ul style="padding-left: 20px; font-size: 12.5px; color: #4b5563; line-height: 1.8;">
            <li>Level 1: <strong>Seedling</strong> (0 Pts)</li>
            <li>Level 2: <strong>Sprout</strong> (100 Pts)</li>
            <li>Level 3: <strong>Sapling</strong> (250 Pts)</li>
            <li>Level 4: <strong>Grower</strong> (500 Pts)</li>
            <li>Level 5: <strong>Cultivator</strong> (900 Pts)</li>
            <li>Level 6: <strong>Harvester</strong> (1.400 Pts)</li>
            <li>Level 7: <strong>Mentor</strong> (2.000 Pts)</li>
            <li>Level 8: <strong>Evergreen</strong> (3.000 Pts)</li>
          </ul>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline" @click="isResetModalOpen = false">
            Batal
          </button>
          <button
            type="button"
            class="btn btn-primary"
            :disabled="submitting"
            @click="submitReset"
          >
            {{ submitting ? 'Mereset...' : 'Ya, Reset ke Default' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  ShieldCheck, RotateCcw, Plus, Check, Layers, CheckCircle2,
  Sparkles, Users, TrendingUp, Search, AlertTriangle, Pencil, Trash2, X,
  Sprout, Leaf, TreePine, Flame, Award, Star, Crown, Zap
} from 'lucide-vue-next'
import api from '@/api/client'
import { useToastStore } from '@/stores/toast'

const toastStore = useToastStore()

// State
const loading = ref(false)
const saving = ref(false)
const submitting = ref(false)
const levels = ref([])
const initialLevels = ref([])
const stats = ref({
  total_levels: 0,
  active_levels: 0,
  inactive_levels: 0,
  max_points: 0,
  total_users: 0,
})

// Search & Filter
const searchQuery = ref('')
const filterStatus = ref('all') // 'all' | 'active' | 'inactive'

// Modals
const isCreateModalOpen = ref(false)
const isEditModalOpen = ref(false)
const isDeleteModalOpen = ref(false)
const isResetModalOpen = ref(false)
const selectedLevel = ref(null)

// Forms
const createForm = ref({
  level: 1,
  name: '',
  min_points: 0,
  description: '',
  badge_icon: 'award',
  badge_color: '#10b981',
  is_active: true,
})

const editForm = ref({
  id: null,
  level: 1,
  name: '',
  min_points: 0,
  description: '',
  badge_icon: 'award',
  badge_color: '#10b981',
  is_active: true,
})

// Available Color Palettes
const availableColors = [
  { name: 'Emerald', hex: '#10b981' },
  { name: 'Forest', hex: '#059669' },
  { name: 'Teal', hex: '#0d9488' },
  { name: 'Sky Blue', hex: '#3b82f6' },
  { name: 'Indigo', hex: '#6366f1' },
  { name: 'Purple', hex: '#8b5cf6' },
  { name: 'Rose', hex: '#ec4899' },
  { name: 'Amber Gold', hex: '#f59e0b' },
  { name: 'Crimson', hex: '#ef4444' },
]

// Available Icon Options
const availableIcons = [
  { key: 'sprout', name: 'Sprout', component: Sprout },
  { key: 'leaf', name: 'Leaf', component: Leaf },
  { key: 'tree', name: 'Tree', component: TreePine },
  { key: 'flame', name: 'Flame', component: Flame },
  { key: 'award', name: 'Award', component: Award },
  { key: 'star', name: 'Star', component: Star },
  { key: 'sparkles', name: 'Sparkles', component: Sparkles },
  { key: 'crown', name: 'Crown', component: Crown },
  { key: 'zap', name: 'Zap', component: Zap },
]

// Icon mapper helper
const getIconComponent = (key) => {
  switch (key) {
    case 'sprout':
      return Sprout
    case 'leaf':
      return Leaf
    case 'tree':
      return TreePine
    case 'flame':
      return Flame
    case 'star':
      return Star
    case 'sparkles':
      return Sparkles
    case 'crown':
      return Crown
    case 'zap':
      return Zap
    default:
      return Award
  }
}

// Format numbers in ID-ID style
const formatNumber = (num) => {
  return new Intl.NumberFormat('id-ID').format(num || 0)
}

// Active count computed
const activeCount = computed(() => {
  return levels.value.filter((l) => l.is_active).length
})

// Computed roadmap levels (sorted by min_points and level)
const roadmapLevels = computed(() => {
  return [...levels.value]
    .filter((l) => l.is_active)
    .sort((a, b) => a.min_points - b.min_points || a.level - b.level)
})

// Filtered levels for table
const filteredLevels = computed(() => {
  return levels.value.filter((lvl) => {
    // Status filter
    if (filterStatus.value === 'active' && !lvl.is_active) return false
    if (filterStatus.value === 'inactive' && lvl.is_active) return false

    // Search query
    if (searchQuery.value.trim()) {
      const q = searchQuery.value.toLowerCase().trim()
      const matchName = lvl.name.toLowerCase().includes(q)
      const matchLevel = String(lvl.level).includes(q)
      const matchDesc = lvl.description ? lvl.description.toLowerCase().includes(q) : false
      if (!matchName && !matchLevel && !matchDesc) return false
    }

    return true
  })
})

// Modified count (detects inline changes vs initial)
const modifiedCount = computed(() => {
  if (!initialLevels.value.length) return 0
  let count = 0
  for (const lvl of levels.value) {
    const init = initialLevels.value.find((i) => i.id === lvl.id)
    if (!init) continue
    if (
      init.name !== lvl.name ||
      init.level !== lvl.level ||
      init.min_points !== lvl.min_points ||
      init.is_active !== lvl.is_active
    ) {
      count++
    }
  }
  return count
})

// Calculate tier range text (e.g. "0 - 99 Pts", "100 - 249 Pts", "3000+ Pts")
const getTierRangeLabel = (lvl) => {
  const activeSorted = [...levels.value]
    .filter((l) => l.is_active)
    .sort((a, b) => a.min_points - b.min_points)

  const idx = activeSorted.findIndex((l) => l.id === lvl.id)
  if (idx === -1) {
    return `${formatNumber(lvl.min_points)} Pts (Non-aktif)`
  }

  const next = activeSorted[idx + 1]
  if (next) {
    const max = next.min_points - 1
    return `${formatNumber(lvl.min_points)} – ${formatNumber(max)} Pts`
  }

  return `${formatNumber(lvl.min_points)}+ Pts (Maksimal)`
}

// Calculate user percentage
const getUserPercentage = (count) => {
  if (!stats.value.total_users || !count) return 0
  return Math.round((count / stats.value.total_users) * 100)
}

// Fetch all levels from API
const fetchLevels = async () => {
  loading.value = true
  try {
    const res = await api.get('/admin/level-rules')
    levels.value = res.data.levels || []
    initialLevels.value = JSON.parse(JSON.stringify(res.data.levels || []))
    stats.value = res.data.stats || stats.value
  } catch (err) {
    console.error('Failed to load level rules', err)
    toastStore.error('Gagal memuat data tingkatan level.')
  } finally {
    loading.value = false
  }
}

// Save all levels batch
const saveAllLevels = async () => {
  saving.value = true
  try {
    const payload = {
      levels: levels.value.map((lvl) => ({
        id: lvl.id,
        level: lvl.level,
        name: lvl.name,
        min_points: lvl.min_points,
        description: lvl.description,
        badge_icon: lvl.badge_icon,
        badge_color: lvl.badge_color,
        is_active: lvl.is_active,
      })),
    }

    const res = await api.put('/admin/level-rules/batch', payload)
    toastStore.success(res.data.message || 'Semua tingkatan level berhasil disimpan.')
    await fetchLevels()
  } catch (err) {
    console.error('Failed to batch save levels', err)
    toastStore.error('Gagal menyimpan perubahan tingkatan level.')
  } finally {
    saving.value = false
  }
}

// Quick inline toggle
const handleInlineToggle = (lvl) => {
  // If user disables the last active level, alert them
  if (!lvl.is_active && activeCount.value === 0) {
    lvl.is_active = true
    toastStore.error('Minimal harus ada 1 level aktif dalam sistem.')
  }
}

// Modal Triggers
const openCreateModal = () => {
  const maxLevel = levels.value.length ? Math.max(...levels.value.map((l) => l.level)) : 0
  const maxPoints = levels.value.length ? Math.max(...levels.value.map((l) => l.min_points)) : 0

  createForm.value = {
    level: maxLevel + 1,
    name: '',
    min_points: maxPoints + 500,
    description: '',
    badge_icon: 'award',
    badge_color: '#10b981',
    is_active: true,
  }
  isCreateModalOpen.value = true
}

const openEditModal = (lvl) => {
  editForm.value = {
    id: lvl.id,
    level: lvl.level,
    name: lvl.name,
    min_points: lvl.min_points,
    description: lvl.description || '',
    badge_icon: lvl.badge_icon || 'award',
    badge_color: lvl.badge_color || '#10b981',
    is_active: lvl.is_active,
  }
  isEditModalOpen.value = true
}

const openDeleteModal = (lvl) => {
  selectedLevel.value = lvl
  isDeleteModalOpen.value = true
}

// Submit Create
const submitCreate = async () => {
  submitting.value = true
  try {
    const res = await api.post('/admin/level-rules', createForm.value)
    toastStore.success(res.data.message)
    isCreateModalOpen.value = false
    await fetchLevels()
  } catch (err) {
    console.error('Failed to create level', err)
    toastStore.error(err.response?.data?.message || 'Gagal menambahkan level baru.')
  } finally {
    submitting.value = false
  }
}

// Submit Edit
const submitEdit = async () => {
  submitting.value = true
  try {
    const res = await api.put(`/admin/level-rules/${editForm.value.id}`, editForm.value)
    toastStore.success(res.data.message)
    isEditModalOpen.value = false
    await fetchLevels()
  } catch (err) {
    console.error('Failed to update level', err)
    toastStore.error(err.response?.data?.message || 'Gagal memperbarui level.')
  } finally {
    submitting.value = false
  }
}

// Submit Delete
const submitDelete = async () => {
  if (!selectedLevel.value) return
  submitting.value = true
  try {
    const res = await api.delete(`/admin/level-rules/${selectedLevel.value.id}`)
    toastStore.success(res.data.message)
    isDeleteModalOpen.value = false
    selectedLevel.value = null
    await fetchLevels()
  } catch (err) {
    console.error('Failed to delete level', err)
    toastStore.error(err.response?.data?.message || 'Gagal menghapus level.')
  } finally {
    submitting.value = false
  }
}

// Submit Reset
const submitReset = async () => {
  submitting.value = true
  try {
    const res = await api.post('/admin/level-rules/reset-defaults')
    toastStore.success(res.data.message)
    isResetModalOpen.value = false
    await fetchLevels()
  } catch (err) {
    console.error('Failed to reset level rules', err)
    toastStore.error('Gagal mereset aturan level.')
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  fetchLevels()
})
</script>

<style scoped src="@/styles/features/admin-level-rules.css"></style>
