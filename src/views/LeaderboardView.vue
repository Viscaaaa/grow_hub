<template>
  <div class="page leaderboard-page">
    <div class="page-header">
      <div>
        <h1 class="page-title">Employee Leaderboard</h1>
        <p class="page-subtitle">Recognizing retail employees most active in sharing knowledge, ideas, and collaborative discussions.</p>
      </div>

      <!-- Department Filter Dropdown -->
      <div class="department-filter">
        <select v-model="selectedDept" @change="fetchLeaderboard" class="select">
          <option value="">All Departments</option>
          <option v-for="d in departments" :key="d" :value="d">{{ d }}</option>
        </select>
      </div>
    </div>

    <!-- Period Tabs -->
    <div class="tabs mb-4">
      <button
        class="tab"
        :class="{ active: period === 'week' }"
        @click="switchPeriod('week')"
      >
        <span>This Week</span>
      </button>

      <button
        class="tab"
        :class="{ active: period === 'month' }"
        @click="switchPeriod('month')"
      >
        <span>This Month</span>
      </button>

      <button
        class="tab"
        :class="{ active: period === 'all' }"
        @click="switchPeriod('all')"
      >
        <span>All-Time</span>
      </button>
    </div>

    <!-- Top 3 Podium Cards -->
    <div v-if="!loading && topThree.length > 0" class="podium-grid mb-4">
      <!-- 2nd Place (Silver) -->
      <div v-if="topThree[1]" class="podium-card rank-2-card">
        <div class="podium-medal silver">2</div>
        <router-link :to="`/profile/${topThree[1].id}`" class="avatar podium-avatar">
          <img v-if="topThree[1].avatar_url" :src="topThree[1].avatar_url" :alt="topThree[1].name" />
          <span v-else>{{ initials(topThree[1].name) }}</span>
        </router-link>
        <router-link :to="`/profile/${topThree[1].id}`" class="bold small text-dark truncate">
          {{ topThree[1].name }}
        </router-link>
        <span class="small muted truncate">{{ topThree[1].department }}</span>
        <div class="podium-score">
          <span class="bold text-primary">{{ formatNumber(topThree[1].score) }}</span>
          <span class="small muted"> Pts</span>
        </div>
      </div>

      <!-- 1st Place (Gold) -->
      <div v-if="topThree[0]" class="podium-card rank-1-card">
        <Crown :size="24" class="text-amber crown-icon" />
        <div class="podium-medal gold">1</div>
        <router-link :to="`/profile/${topThree[0].id}`" class="avatar podium-avatar first-avatar">
          <img v-if="topThree[0].avatar_url" :src="topThree[0].avatar_url" :alt="topThree[0].name" />
          <span v-else>{{ initials(topThree[0].name) }}</span>
        </router-link>
        <router-link :to="`/profile/${topThree[0].id}`" class="bold text-dark truncate">
          {{ topThree[0].name }}
        </router-link>
        <span class="small muted truncate">{{ topThree[0].department }}</span>
        <div class="podium-score">
          <span class="bold text-primary" style="font-size: 18px;">{{ formatNumber(topThree[0].score) }}</span>
          <span class="small muted"> Pts</span>
        </div>
      </div>

      <!-- 3rd Place (Bronze) -->
      <div v-if="topThree[2]" class="podium-card rank-3-card">
        <div class="podium-medal bronze">3</div>
        <router-link :to="`/profile/${topThree[2].id}`" class="avatar podium-avatar">
          <img v-if="topThree[2].avatar_url" :src="topThree[2].avatar_url" :alt="topThree[2].name" />
          <span v-else>{{ initials(topThree[2].name) }}</span>
        </router-link>
        <router-link :to="`/profile/${topThree[2].id}`" class="bold small text-dark truncate">
          {{ topThree[2].name }}
        </router-link>
        <span class="small muted truncate">{{ topThree[2].department }}</span>
        <div class="podium-score">
          <span class="bold text-primary">{{ formatNumber(topThree[2].score) }}</span>
          <span class="small muted"> Pts</span>
        </div>
      </div>
    </div>

    <!-- Leaderboard Table -->
    <div class="card overflow-hidden">
      <div v-if="loading" class="stack p-4">
        <div class="skeleton" style="height: 50px;"></div>
        <div class="skeleton" style="height: 50px;"></div>
      </div>

      <table v-else class="table">
        <thead>
          <tr>
            <th style="width: 70px;">Rank</th>
            <th>Employee</th>
            <th>Department</th>
            <th>Streak</th>
            <th style="text-align: right;">Points Earned</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="user in leaders"
            :key="user.id"
            :class="{ 'my-rank-row': user.id === authStore.user?.id }"
          >
            <td>
              <span class="rank-badge" :class="`rank-badge-${user.rank}`">
                {{ user.rank }}
              </span>
            </td>
            <td>
              <router-link :to="`/profile/${user.id}`" class="row" style="gap: 10px; text-decoration: none; color: inherit;">
                <div class="avatar" style="width: 36px; height: 36px; font-size: 13px;">
                  <img v-if="user.avatar_url" :src="user.avatar_url" :alt="user.name" />
                  <span v-else>{{ initials(user.name) }}</span>
                </div>
                <div>
                  <div class="bold text-dark hover-green">{{ user.name }}</div>
                  <div class="small muted">NIP {{ user.nip }}</div>
                </div>
              </router-link>
            </td>
            <td>
              <span class="badge badge-gray small">{{ user.department || 'Retail' }}</span>
            </td>
            <td>
              <span class="row small bold text-orange" style="gap: 4px;">
                <Flame :size="14" />
                <span>{{ user.current_streak }} Days</span>
              </span>
            </td>
            <td style="text-align: right;">
              <span class="bold text-primary" style="font-size: 15px;">
                {{ formatNumber(user.score) }}
              </span>
              <span class="small muted"> Pts</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- My Rank Sticky Footer -->
    <div v-if="myRankInfo" class="card card-pad card-dark my-rank-sticky mt-4">
      <div class="row" style="gap: 12px;">
        <div class="my-rank-circle">
          #{{ myRankInfo.rank || '-' }}
        </div>
        <div>
          <div class="bold">Your Rank</div>
          <div class="small opacity-80">Stay active in sharing and engaging to climb the ranks!</div>
        </div>
      </div>

      <div class="row" style="gap: 16px;">
        <div class="text-right">
          <span class="small opacity-80">Points ({{ periodTitle }}):</span>
          <div class="bold" style="font-size: 18px;">{{ formatNumber(myRankInfo.score || 0) }} Pts</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Crown, Flame } from 'lucide-vue-next'
import api from '@/api/client'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

const loading = ref(true)
const period = ref('all')
const selectedDept = ref('')
const leaders = ref([])
const myRankInfo = ref(null)
const departments = ref([])

const fetchLeaderboard = async () => {
  loading.value = true
  try {
    const res = await api.get('/leaderboard', {
      params: {
        period: period.value,
        department: selectedDept.value || undefined,
      },
    })
    leaders.value = res.data.leaders
    myRankInfo.value = res.data.me
    departments.value = res.data.departments || []
  } catch (err) {
    console.error('Failed leaderboard', err)
  } finally {
    loading.value = false
  }
}

const switchPeriod = (p) => {
  period.value = p
  fetchLeaderboard()
}

const topThree = computed(() => {
  return leaders.value.slice(0, 3)
})

const periodTitle = computed(() => {
  if (period.value === 'week') return 'This Week'
  if (period.value === 'month') return 'This Month'
  return 'All-Time'
})

onMounted(() => {
  fetchLeaderboard()
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

<style scoped src="@/styles/features/leaderboard.css"></style>
