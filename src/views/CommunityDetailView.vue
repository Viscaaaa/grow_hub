<template>
  <div class="page community-detail-page">
    <div v-if="loading" class="stack">
      <div class="skeleton" style="height: 140px;"></div>
      <div class="skeleton" style="height: 200px;"></div>
    </div>

    <div v-else-if="community" class="stack">
      <router-link to="/communities" class="btn btn-ghost btn-sm mb-2" style="padding-left: 0;">
        <ArrowLeft :size="16" />
        <span>Back to Communities</span>
      </router-link>

      <!-- Community Header Card -->
      <div class="card card-pad community-header-card">
        <div class="row-between flex-wrap gap-4">
          <div class="row" style="gap: 16px;">
            <div
              class="community-large-icon"
              :style="{ background: `${community.color || '#1e8c45'}20`, color: community.color || '#1e8c45' }"
            >
              <Users :size="32" />
            </div>
            <div>
              <h1 class="community-header-name">{{ community.name }}</h1>
              <p class="community-header-desc small text-2 mt-1">{{ community.description }}</p>
              <div class="row small muted mt-2" style="gap: 12px;">
                <span><strong>{{ community.members_count }}</strong> Members</span>
                <span>•</span>
                <span><strong>{{ community.contents_count }}</strong> Published Posts</span>
              </div>
            </div>
          </div>

          <button
            class="btn btn-lg"
            :class="community.is_member ? 'btn-outline text-primary' : 'btn-primary'"
            @click="toggleJoin"
          >
            <Check v-if="community.is_member" :size="18" />
            <UserPlus v-else :size="18" />
            <span>{{ community.is_member ? 'Joined' : 'Join Community' }}</span>
          </button>
        </div>
      </div>

      <!-- Split Layout: Feed (Left) vs Top Members (Right) -->
      <div class="community-split mt-4">
        <div class="community-feed-col stack">
          <div class="row-between mb-2">
            <h2 class="card-title">Discussions & Community Posts</h2>
            <router-link :to="`/create?community_id=${community.id}`" class="btn btn-primary btn-sm">
              <Plus :size="14" />
              <span>Share in this Community</span>
            </router-link>
          </div>

          <div v-if="feedLoading" class="stack">
            <div class="skeleton" style="height: 180px;"></div>
            <div class="skeleton" style="height: 180px;"></div>
          </div>

          <div v-else-if="contents.length > 0" class="stack">
            <ContentCard
              v-for="item in contents"
              :key="item.id"
              :item="item"
            />
          </div>

          <div v-else class="card card-pad empty">
            <p class="small muted">No content in this community yet. Be the first to share!</p>
          </div>
        </div>

        <!-- Sidebar: Top Members in Community -->
        <aside class="community-side-col">
          <div class="card card-pad">
            <div class="card-title mb-3">Top Community Members</div>
            <div class="stack" style="gap: 10px;">
              <router-link
                v-for="m in topMembers"
                :key="m.id"
                :to="`/profile/${m.id}`"
                class="row-between member-row"
              >
                <div class="row" style="gap: 10px;">
                  <div class="avatar" style="width: 34px; height: 34px; font-size: 12px;">
                    <img v-if="m.avatar_url" :src="m.avatar_url" :alt="m.name" />
                    <span v-else>{{ initials(m.name) }}</span>
                  </div>
                  <div class="truncate">
                    <span class="bold small truncate text-dark">{{ m.name }}</span>
                    <span class="small muted truncate">{{ m.department }}</span>
                  </div>
                </div>
                <span class="small bold text-primary">{{ m.points }} Pts</span>
              </router-link>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { ArrowLeft, Users, Check, UserPlus, Plus } from 'lucide-vue-next'
import api from '@/api/client'
import { useToastStore } from '@/stores/toast'
import ContentCard from '@/components/common/ContentCard.vue'

const route = useRoute()
const toastStore = useToastStore()

const loading = ref(true)
const feedLoading = ref(true)
const community = ref(null)
const topMembers = ref([])
const contents = ref([])

const fetchCommunity = async () => {
  loading.value = true
  try {
    const res = await api.get(`/communities/${route.params.slug}`)
    community.value = res.data.community
    topMembers.value = res.data.top_members || []
    await fetchCommunityFeed()
  } catch (err) {
    toastStore.error('Failed to load community.')
  } finally {
    loading.value = false
  }
}

const fetchCommunityFeed = async () => {
  if (!community.value) return
  feedLoading.value = true
  try {
    const res = await api.get('/contents', {
      params: { community_id: community.value.id },
    })
    contents.value = res.data.data
  } catch (err) {
    console.error('Failed community feed', err)
  } finally {
    feedLoading.value = false
  }
}

const toggleJoin = async () => {
  try {
    const res = await api.post(`/communities/${community.value.id}/join`)
    community.value.is_member = res.data.is_member
    community.value.members_count = res.data.members_count
    toastStore.success(res.data.is_member ? 'Joined community!' : 'Left community.')
  } catch (err) {
    toastStore.error('Failed to update membership.')
  }
}

onMounted(() => {
  fetchCommunity()
})

const initials = (name) => {
  if (!name) return 'GH'
  return name.split(' ').map((n) => n[0]).slice(0, 2).join('').toUpperCase()
}
</script>

<style scoped src="@/styles/features/community-detail.css"></style>
