<template>
  <div v-if="isOpen" class="modal-backdrop" @click.self="close">
    <div class="modal">
      <div class="modal-head">
        <div class="card-title">
          <Share2 :size="20" class="text-primary" />
          <span>Share Content</span>
        </div>
        <button class="btn btn-ghost btn-icon btn-sm" @click="close" aria-label="Close">
          <X :size="18" />
        </button>
      </div>

      <div class="modal-body stack">
        <p class="text-2 small">
          Share this knowledge with colleagues. Both you and the author will earn appreciation points!
        </p>

        <!-- Content preview summary -->
        <div class="share-preview">
          <div class="share-cat badge badge-gray">{{ content?.category?.name }}</div>
          <div class="share-title bold clamp-2">{{ content?.title }}</div>
          <div class="share-author small muted">By {{ content?.author?.name }}</div>
        </div>

        <div class="field">
          <label class="label">Content Link</label>
          <div class="row" style="gap: 8px;">
            <input :value="shareUrl" class="input" readonly />
            <button class="btn btn-primary" @click="copyLink">
              <Check v-if="copied" :size="16" />
              <Copy v-else :size="16" />
              <span>{{ copied ? 'Copied!' : 'Copy' }}</span>
            </button>
          </div>
        </div>

        <div class="divider"></div>

        <div class="field">
          <label class="label">Select Share Channel</label>
          <div class="share-grid">
            <button class="share-btn" @click="shareChannel('whatsapp')">
              <div class="share-btn-icon bg-green">
                <MessageCircle :size="20" />
              </div>
              <span>WhatsApp</span>
            </button>
            <button class="share-btn" @click="shareChannel('email')">
              <div class="share-btn-icon bg-blue">
                <Mail :size="20" />
              </div>
              <span>Email</span>
            </button>
            <button class="share-btn" @click="shareChannel('teams')">
              <div class="share-btn-icon bg-purple">
                <Users :size="20" />
              </div>
              <span>Teams / Slack</span>
            </button>
            <button class="share-btn" @click="shareChannel('link')">
              <div class="share-btn-icon bg-dark">
                <Link2 :size="20" />
              </div>
              <span>Copy Link</span>
            </button>
          </div>
        </div>
      </div>

      <div class="modal-foot">
        <button class="btn btn-outline" @click="close">Close</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Share2, X, Copy, Check, MessageCircle, Mail, Users, Link2 } from 'lucide-vue-next'
import api from '@/api/client'
import { useToastStore } from '@/stores/toast'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  isOpen: Boolean,
  content: Object,
})

const emit = defineEmits(['close', 'shared'])
const toastStore = useToastStore()
const authStore = useAuthStore()
const copied = ref(false)

const shareUrl = computed(() => {
  if (!props.content?.id) return ''
  return `${window.location.origin}/content/${props.content.id}`
})

const close = () => {
  copied.value = false
  emit('close')
}

const recordShare = async (channel) => {
  try {
    const res = await api.post(`/contents/${props.content.id}/share`, { channel })
    if (res.data.points_earned > 0) {
      toastStore.pointsEarned(res.data.points_earned, 'Thank you for sharing knowledge!')
      authStore.mutatePoints(res.data.points_earned)
    }
    emit('shared', res.data.shares_count)
  } catch (err) {
    console.error('Record share failed', err)
  }
}

const copyLink = async () => {
  try {
    await navigator.clipboard.writeText(shareUrl.value)
    copied.value = true
    toastStore.success('Link copied to clipboard!')
    await recordShare('copy_link')
    setTimeout(() => {
      copied.value = false
    }, 2500)
  } catch (e) {
    toastStore.error('Failed to copy link.')
  }
}

const shareChannel = async (channel) => {
  const text = encodeURIComponent(`Read "${props.content?.title}" on GrowHub:\n${shareUrl.value}`)
  if (channel === 'whatsapp') {
    window.open(`https://api.whatsapp.com/send?text=${text}`, '_blank')
  } else if (channel === 'email') {
    window.open(`mailto:?subject=${encodeURIComponent(props.content?.title || 'GrowHub Content')}&body=${text}`, '_blank')
  } else if (channel === 'teams') {
    await copyLink()
    return
  } else {
    await copyLink()
    return
  }
  await recordShare(channel)
}
</script>

<style scoped src="@/styles/components/share-modal.css"></style>
