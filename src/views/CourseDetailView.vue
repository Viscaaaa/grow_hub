 <template>
  <div class="page course-detail-page">
    <div v-if="loading" class="stack">
      <div class="skeleton" style="height: 40px; width: 220px;"></div>
      <div class="skeleton" style="height: 380px;"></div>
    </div>

    <div v-else-if="course" class="stack" style="max-width: 860px; margin: 0 auto;">
      <router-link to="/learning" class="btn btn-ghost btn-sm mb-2" style="padding-left: 0;">
        <ArrowLeft :size="16" />
        <span>Back to Learning Center</span>
      </router-link>

      <!-- Banner Header -->
      <div class="card course-header-card" :style="{ background: course.cover_color || '#1e8c45' }">
        <div class="row-between mb-2">
          <span class="badge badge-light-blur">{{ course.category }}</span>
          <span class="badge badge-light-blur">{{ course.level }}</span>
        </div>

        <h1 class="course-header-title">{{ course.title }}</h1>
        <p class="course-header-summary">{{ course.summary }}</p>

        <div class="row-between course-header-meta">
          <div class="row small text-white" style="gap: 16px;">
            <span class="row" style="gap: 6px;"><Clock :size="16" /> {{ course.duration_minutes }} Min Read</span>
            <span class="row" style="gap: 6px;"><Users :size="16" /> {{ course.completions_count || 0 }} Employees Completed</span>
          </div>

          <div class="points-reward-pill">
            <Sparkles :size="16" />
            <span>Reward: +{{ course.points_reward }} Pts</span>
          </div>
        </div>
      </div>

      <!-- Course Body & Reading Material -->
      <div class="card card-pad course-body-card">
        <div class="course-markdown" v-html="renderedMarkdown"></div>

        <!-- Completion Call to Action Box -->
        <div class="completion-box mt-6">
          <div v-if="course.is_completed" class="completion-done">
            <CheckCircle2 :size="24" class="text-primary" />
            <div>
              <div class="bold text-dark">Congratulations! You have completed this module.</div>
              <p class="small muted">Reward points have been added to your profile balance.</p>
            </div>
          </div>

          <div v-else class="completion-action">
            <div>
              <div class="bold text-dark">Finished reading and understanding the material?</div>
              <p class="small text-2">Claim your reward to add <strong>+{{ course.points_reward }} Points</strong> to your profile!</p>
            </div>
            <button
              class="btn btn-primary btn-lg"
              @click="completeCourse"
              :disabled="completing"
            >
              <span v-if="completing" class="spinner"></span>
              <Check v-else :size="18" />
              <span>Complete & Claim Points</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import {
  ArrowLeft, Clock, Users, Sparkles, CheckCircle2, Check
} from 'lucide-vue-next'
import api from '@/api/client'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'

const route = useRoute()
const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(true)
const completing = ref(false)
const course = ref(null)

const fetchCourse = async () => {
  loading.value = true
  try {
    const res = await api.get(`/courses/${route.params.slug}`)
    course.value = res.data
  } catch (err) {
    toastStore.error('Failed to load learning module.')
  } finally {
    loading.value = false
  }
}

const completeCourse = async () => {
  completing.value = true
  try {
    const res = await api.post(`/courses/${course.value.id}/complete`)
    course.value.is_completed = true
    course.value.completions_count = (course.value.completions_count || 0) + 1
    if (res.data.points_earned > 0) {
      toastStore.pointsEarned(res.data.points_earned, 'Learning module completed successfully!')
      authStore.mutatePoints(res.data.points_earned)
    } else {
      toastStore.info(res.data.message)
    }
  } catch (err) {
    toastStore.error('Failed to complete module.')
  } finally {
    completing.value = false
  }
}

// Simple safe markdown renderer
const renderedMarkdown = computed(() => {
  if (!course.value?.body) return ''
  return course.value.body
    .replace(/^## (.*$)/gim, '<h2 class="md-h2">$1</h2>')
    .replace(/^### (.*$)/gim, '<h3 class="md-h3">$1</h3>')
    .replace(/\*\*(.*?)\*\*/gim, '<strong>$1</strong>')
    .replace(/\*(.*?)\*/gim, '<em>$1</em>')
    .replace(/^- (.*$)/gim, '<li class="md-li">$1</li>')
    .replace(/\n\n/gim, '<br /><br />')
})

onMounted(() => {
  fetchCourse()
})
</script>

<style scoped src="@/styles/features/course-detail.css"></style>
