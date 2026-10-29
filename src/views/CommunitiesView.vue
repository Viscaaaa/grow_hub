<template>
  <div class="page communities-page">
    <div class="page-header">
      <div>
        <h1 class="page-title">Retail Communities</h1>
        <p class="page-subtitle">Join retail special-interest groups and departmental circles to discuss, share challenges, and learn together.</p>
      </div>

      <button class="btn btn-primary" @click="createModalOpen = true">
        <Plus :size="18" />
        <span>Create New Community</span>
      </button>
    </div>

    <!-- Filter chips (All vs My Communities) -->
    <div class="chips mb-4">
      <button
        class="chip"
        :class="{ active: filterJoined === false }"
        @click="filterJoined = false; fetchCommunities()"
      >
        Browse All
      </button>
      <button
        class="chip"
        :class="{ active: filterJoined === true }"
        @click="filterJoined = true; fetchCommunities()"
      >
        My Communities
      </button>
    </div>

    <!-- Communities Grid -->
    <div v-if="loading" class="grid grid-3">
      <div class="skeleton" style="height: 220px;"></div>
      <div class="skeleton" style="height: 220px;"></div>
      <div class="skeleton" style="height: 220px;"></div>
    </div>

    <div v-else-if="communities.length > 0" class="grid grid-3 communities-grid">
      <div
        v-for="comm in communities"
        :key="comm.id"
        class="card card-pad card-hover community-card"
      >
        <div class="community-card-head mb-3">
          <div
            class="community-icon-box"
            :style="{ background: `${comm.color || '#1e8c45'}15`, color: comm.color || '#1e8c45' }"
          >
            <component :is="getIcon(comm.icon)" :size="24" />
          </div>

          <button
            class="btn btn-sm"
            :class="comm.is_member ? 'btn-outline text-primary' : 'btn-primary'"
            @click="toggleJoin(comm)"
            :disabled="joinLoading[comm.id]"
          >
            <Check v-if="comm.is_member" :size="14" />
            <UserPlus v-else :size="14" />
            <span>{{ comm.is_member ? 'Joined' : 'Join' }}</span>
          </button>
        </div>

        <router-link :to="`/communities/${comm.slug}`" class="community-info-link">
          <h2 class="community-name bold text-dark hover-green">{{ comm.name }}</h2>
          <p class="community-desc small text-2 clamp-2 mt-1">{{ comm.description }}</p>
        </router-link>

        <div class="row-between pt-3 mt-auto" style="border-top: 1px solid var(--border);">
          <div class="row small muted" style="gap: 12px;">
            <span><strong>{{ comm.members_count || 0 }}</strong> Members</span>
            <span><strong>{{ comm.contents_count || 0 }}</strong> Posts</span>
          </div>

          <router-link :to="`/communities/${comm.slug}`" class="btn btn-ghost btn-icon btn-sm" title="Open Community">
            <ChevronRight :size="16" />
          </router-link>
        </div>
      </div>
    </div>

    <div v-else class="card card-pad empty">
      <div class="empty-icon"><Users :size="32" /></div>
      <h3>You haven't joined any communities yet</h3>
      <p class="small muted">Browse the communities above and click Join to connect with peers.</p>
    </div>

    <!-- Create Community Modal -->
    <div v-if="createModalOpen" class="modal-backdrop" @click.self="createModalOpen = false">
      <div class="modal">
        <div class="modal-head">
          <div class="card-title">
            <Users :size="20" class="text-primary" />
            <span>Create New Retail Community</span>
          </div>
          <button class="btn btn-ghost btn-icon btn-sm" @click="createModalOpen = false">
            <X :size="18" />
          </button>
        </div>

        <form @submit.prevent="createCommunity">
          <div class="modal-body stack">
            <div class="field">
              <label class="label">Community Name <span class="text-red">*</span></label>
              <input
                v-model="newComm.name"
                type="text"
                placeholder="e.g. Merchandising Squad"
                class="input"
                required
              />
            </div>

            <div class="field">
              <label class="label">Description & Purpose</label>
              <textarea
                v-model="newComm.description"
                rows="3"
                placeholder="Describe the focus topic and mission of this community..."
                class="textarea"
              ></textarea>
            </div>
          </div>

          <div class="modal-foot">
            <button type="button" class="btn btn-outline" @click="createModalOpen = false">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="creating">
              <span v-if="creating" class="spinner"></span>
              <span v-else>Create Community</span>
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
  Users, Plus, Check, UserPlus, ChevronRight, X, Building,
  Heart, Compass, Rocket, Crown, Leaf
} from 'lucide-vue-next'
import api from '@/api/client'
import { useToastStore } from '@/stores/toast'

const toastStore = useToastStore()

const loading = ref(true)
const communities = ref([])
const filterJoined = ref(false)
const joinLoading = ref({})

const createModalOpen = ref(false)
const creating = ref(false)
const newComm = ref({ name: '', description: '', icon: 'Users', color: '#1e8c45' })

const iconMap = { Building, Heart, Compass, Rocket, Crown, Leaf, Users }
const getIcon = (name) => iconMap[name] || Users

const fetchCommunities = async () => {
  loading.value = true
  try {
    const res = await api.get('/communities', {
      params: { joined: filterJoined.value ? 1 : 0 },
    })
    communities.value = res.data
  } catch (err) {
    console.error('Failed communities', err)
  } finally {
    loading.value = false
  }
}

const toggleJoin = async (comm) => {
  joinLoading.value[comm.id] = true
  try {
    const res = await api.post(`/communities/${comm.id}/join`)
    comm.is_member = res.data.is_member
    comm.members_count = res.data.members_count
    toastStore.success(res.data.is_member ? `Joined ${comm.name}!` : `Left ${comm.name}.`)
  } catch (err) {
    toastStore.error('Failed to update membership status.')
  } finally {
    joinLoading.value[comm.id] = false
  }
}

const createCommunity = async () => {
  if (!newComm.value.name.trim()) return
  creating.value = true
  try {
    await api.post('/communities', newComm.value)
    toastStore.success('Community created successfully!')
    createModalOpen.value = false
    newComm.value = { name: '', description: '', icon: 'Users', color: '#1e8c45' }
    await fetchCommunities()
  } catch (err) {
    toastStore.error(err.response?.data?.message || 'Failed to create community.')
  } finally {
    creating.value = false
  }
}

onMounted(() => {
  fetchCommunities()
})
</script>

<style scoped src="@/styles/features/communities.css"></style>
