<template>
  <div class="toast-container">
    <transition-group name="toast">
      <div
        v-for="toast in toastStore.toasts"
        :key="toast.id"
        class="toast-item"
        :class="`toast-${toast.type}`"
        @click="toastStore.remove(toast.id)"
      >
        <div class="toast-icon">
          <Sparkles v-if="toast.type === 'points'" :size="20" />
          <CheckCircle2 v-else-if="toast.type === 'success'" :size="20" />
          <AlertCircle v-else-if="toast.type === 'error'" :size="20" />
          <Info v-else :size="20" />
        </div>
        <div class="toast-content">
          <div class="toast-title">{{ toast.title }}</div>
          <div v-if="toast.message" class="toast-message">{{ toast.message }}</div>
        </div>
        <button class="toast-close" aria-label="Close">
          <X :size="16" />
        </button>
      </div>
    </transition-group>
  </div>
</template>

<script setup>
import { useToastStore } from '@/stores/toast'
import { Sparkles, CheckCircle2, AlertCircle, Info, X } from 'lucide-vue-next'

const toastStore = useToastStore()
</script>

<style scoped src="@/styles/components/toast-container.css"></style>
