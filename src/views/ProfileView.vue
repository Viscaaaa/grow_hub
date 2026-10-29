<template>
  <div class="page profile-page">
    <div v-if="loading" class="stack">
      <div class="skeleton" style="height: 200px;"></div>
      <div class="skeleton" style="height: 300px;"></div>
    </div>

    <div v-else-if="profileUser" class="stack">
      <!-- Profile Hero Header Card -->
      <div class="card card-pad profile-hero-card">
        <div class="profile-hero-top">
          <!-- Avatar + Photo upload -->
          <div class="profile-avatar-wrap">
            <div class="avatar profile-avatar">
              <img v-if="profileUser.avatar_url" :src="profileUser.avatar_url" :alt="profileUser.name" />
              <span v-else>{{ initials(profileUser.name) }}</span>
            </div>

            <!-- Upload photo button if viewing own profile -->
            <button
              v-if="isMe"
              class="avatar-upload-btn"
              @click="$refs.avatarInput.click()"
              title="Change Profile Photo"
            >
              <Camera :size="16" />
              <input
                ref="avatarInput"
                type="file"
                accept="image/*"
                style="display: none;"
                @change="handleAvatarUpload"
              />
            </button>
          </div>

          <!-- Profile Info -->
          <div class="profile-meta-info flex-1">
            <div class="row-between flex-wrap gap-2">
              <div>
                <div class="row" style="gap: 8px;">
                  <h1 class="profile-name">{{ profileUser.name }}</h1>
                  <span class="badge badge-dark small">{{ profileUser.role?.toUpperCase() }}</span>
                </div>
                <div class="row muted small mt-1" style="gap: 8px;">
                  <span>NIP: <strong>{{ profileUser.nip }}</strong></span>
                  <span>•</span>
                  <span>{{ profileUser.department }}</span>
                  <span v-if="profileUser.location">• {{ profileUser.location }}</span>
                </div>
              </div>

              <!-- Quick Level Pill -->
              <div class="level-indicator-pill">
                <Award :size="18" class="text-primary" />
                <div class="row" style="gap: 6px;">
                  <span class="bold text-dark">Level {{ profileUser.level_info?.level }}</span>
                  <span class="small bold text-primary">({{ profileUser.level_info?.name }})</span>
                </div>
              </div>
            </div>

            <p v-if="profileUser.bio" class="profile-bio mt-2 text-2 small">{{ profileUser.bio }}</p>

            <!-- Streak & Points Summary Bar -->
            <div class="profile-chips-row mt-3">
              <div class="profile-chip-stat">
                <Sparkles :size="15" class="text-amber" />
                <span><strong>{{ formatNumber(profileUser.points) }}</strong> Points</span>
              </div>

              <div class="profile-chip-stat">
                <Flame :size="15" class="text-orange" />
                <span>Streak: <strong>{{ profileUser.current_streak }}</strong> Days (Record: {{ profileUser.longest_streak }} Days)</span>
              </div>

              <div class="profile-chip-stat">
                <Trophy :size="15" class="text-primary" />
                <span>Rank <strong>#{{ profileRank }}</strong> Global</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-4 mt-2">
        <div class="card card-pad stat">
          <div class="stat-icon bg-green-50">
            <BookOpen :size="20" class="text-primary" />
          </div>
          <div>
            <div class="stat-value">{{ stats.contents || 0 }}</div>
            <div class="stat-label">Published Posts</div>
          </div>
        </div>

        <div class="card card-pad stat">
          <div class="stat-icon bg-red-50">
            <Heart :size="20" class="text-red" />
          </div>
          <div>
            <div class="stat-value">{{ stats.likes || 0 }}</div>
            <div class="stat-label">Total Likes Earned</div>
          </div>
        </div>

        <div class="card card-pad stat">
          <div class="stat-icon bg-blue-50">
            <GraduationCap :size="20" class="text-blue" />
          </div>
          <div>
            <div class="stat-value">{{ stats.courses || 0 }}</div>
            <div class="stat-label">Courses Completed</div>
          </div>
        </div>

        <div class="card card-pad stat">
          <div class="stat-icon bg-amber-50">
            <Users :size="20" class="text-amber" />
          </div>
          <div>
            <div class="stat-value">{{ stats.communities || 0 }}</div>
            <div class="stat-label">Communities Joined</div>
          </div>
        </div>
      </div>

      <!-- Navigation Tabs: Published Content vs Edit Profile / Security (if own) -->
      <div class="tabs mt-4">
        <button
          class="tab"
          :class="{ active: activeTab === 'contents' }"
          @click="activeTab = 'contents'"
        >
          <span>Published Content ({{ contents.length }})</span>
        </button>

        <button
          v-if="isMe"
          class="tab"
          :class="{ active: activeTab === 'edit' }"
          @click="activeTab = 'edit'"
        >
          <span>Edit Profile</span>
        </button>
      </div>

      <!-- Tab 1: Published Content -->
      <div v-if="activeTab === 'contents'" class="mt-2 stack">
        <div v-if="contents.length > 0" class="stack">
          <ContentCard
            v-for="item in contents"
            :key="item.id"
            :item="item"
          />
        </div>
        <div v-else class="card card-pad empty">
          <p class="small muted">This employee has not published any content yet.</p>
        </div>
      </div>

      <!-- Tab 2: Edit Profile (Own profile) -->
      <div v-else-if="activeTab === 'edit' && isMe" class="card card-pad mt-2" style="max-width: 640px;">
        <h2 class="card-title mb-3">Update Your Profile</h2>

        <form @submit.prevent="updateProfile" class="stack">
          <div class="field">
            <label class="label">Full Name</label>
            <input v-model="editForm.name" type="text" class="input" required />
          </div>

          <div class="field">
            <label class="label">Work Email</label>
            <input v-model="editForm.email" type="email" class="input" />
          </div>

          <div class="field">
            <label class="label">Work Location / Store Branch</label>
            <input v-model="editForm.location" type="text" placeholder="e.g. Store Jakarta 01" class="input" />
          </div>

          <div class="field">
            <label class="label">Short Bio</label>
            <textarea
              v-model="editForm.bio"
              rows="3"
              placeholder="Write your personal motto or areas of expertise..."
              class="textarea"
            ></textarea>
          </div>

          <button type="submit" class="btn btn-primary" :disabled="saving">
            <span v-if="saving" class="spinner"></span>
            <span v-else>Save Changes</span>
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import {
  Camera, Award, Sparkles, Flame, Trophy, BookOpen, Heart,
  GraduationCap, Users
} from 'lucide-vue-next'
import api from '@/api/client'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import ContentCard from '@/components/common/ContentCard.vue'

const route = useRoute()
const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(true)
const profileUser = ref(null)
const profileRank = ref(1)
const stats = ref({})
const contents = ref([])
const activeTab = ref('contents')

const isMe = computed(() => {
  if (!route.params.id) return true
  return Number(route.params.id) === authStore.user?.id
})

const editForm = ref({ name: '', email: '', location: '', bio: '' })
const saving = ref(false)

const fetchProfile = async () => {
  loading.value = true
  const targetId = route.params.id || authStore.user?.id
  try {
    const res = await api.get(`/users/${targetId}`)
    profileUser.value = res.data.user
    profileRank.value = res.data.rank
    stats.value = res.data.stats
    contents.value = res.data.contents

    if (isMe.value) {
      editForm.value = {
        name: profileUser.value.name,
        email: profileUser.value.email || '',
        location: profileUser.value.location || '',
        bio: profileUser.value.bio || '',
      }
    }
  } catch (err) {
    toastStore.error('Failed to load profile.')
  } finally {
    loading.value = false
  }
}

const handleAvatarUpload = async (e) => {
  const file = e.target.files?.[0]
  if (!file) return

  const formData = new FormData()
  formData.append('avatar', file)

  try {
    const res = await api.post('/profile/avatar', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    authStore.updateUser(res.data.user)
    profileUser.value.avatar_url = res.data.user.avatar_url
    toastStore.success('Profile photo updated successfully!')
  } catch (err) {
    toastStore.error('Failed to upload profile photo.')
  }
}

const updateProfile = async () => {
  saving.value = true
  try {
    const res = await api.put('/profile', editForm.value)
    authStore.updateUser(res.data.user)
    profileUser.value = res.data.user
    toastStore.success('Profile updated successfully.')
  } catch (err) {
    toastStore.error(err.response?.data?.message || 'Failed to update profile.')
  } finally {
    saving.value = false
  }
}

watch(
  () => route.params.id,
  () => {
    fetchProfile()
  }
)

onMounted(() => {
  fetchProfile()
})

const formatNumber = (num) => {
  if (!num) return '0'
  return new Intl.NumberFormat('en-US').format(num)
}

const initials = (name) => {
  if (!name) return 'GH'
  return name.split(' ').map((n) => n[0]).slice(0, 2).join('').toUpperCase()
}
</script>

<style scoped src="@/styles/features/profile.css"></style>
