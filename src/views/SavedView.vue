<template>
  <div class="page saved-page">
    <div class="page-header">
      <div>
        <h1 class="page-title">Saved Knowledge</h1>
        <p class="page-subtitle">Articles, SOPs, tips, and ideas you bookmarked for quick daily reference.</p>
      </div>

      <router-link to="/feed" class="btn btn-outline">
        <BookOpen :size="16" />
        <span>Explore Feed</span>
      </router-link>
    </div>

    <div v-if="loading" class="stack">
      <div class="skeleton" style="height: 180px;"></div>
      <div class="skeleton" style="height: 180px;"></div>
    </div>

    <div v-else-if="items.length > 0" class="stack">
      <ContentCard
        v-for="item in items"
        :key="item.id"
        :item="item"
      />
    </div>

    <div v-else class="card card-pad empty">
      <div class="empty-icon bg-green-50">
        <Bookmark :size="32" class="text-primary" />
      </div>
      <h3>No saved content yet</h3>
      <p class="small muted">When you find helpful articles or SOPs in the Feed, click <strong>Save</strong> to bookmark them here for quick access.</p>
      <router-link to="/feed" class="btn btn-primary btn-sm mt-3">
        <span>Open Knowledge Feed</span>
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Bookmark, BookOpen } from 'lucide-vue-next'
import api from '@/api/client'
import ContentCard from '@/components/common/ContentCard.vue'

const loading = ref(true)
const items = ref([])

const fetchSaved = async () => {
  loading.value = true
  try {
    const res = await api.get('/my/saved')
    items.value = res.data.data
  } catch (err) {
    console.error('Failed to load saved items', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchSaved()
})
</script>

<style scoped src="@/styles/features/saved.css"></style>
