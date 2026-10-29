import { defineStore } from 'pinia'

export const useToastStore = defineStore('toast', {
  state: () => ({
    toasts: [],
  }),

  actions: {
    add({ title, message, type = 'success', duration = 4000, points = null }) {
      const id = Date.now() + Math.random()
      this.toasts.push({ id, title, message, type, points })
      setTimeout(() => {
        this.remove(id)
      }, duration)
    },

    success(message, title = 'Success', points = null) {
      this.add({ title, message, type: 'success', points })
    },

    error(message, title = 'Error') {
      this.add({ title, message, type: 'error' })
    },

    info(message, title = 'Information') {
      this.add({ title, message, type: 'info' })
    },

    pointsEarned(amount, message = 'Points added to your profile!') {
      this.add({
        title: `+${amount} Points Earned! 🎉`,
        message,
        type: 'points',
        points: amount,
      })
    },

    remove(id) {
      this.toasts = this.toasts.filter(t => t.id !== id)
    },
  },
})
