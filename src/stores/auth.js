import { defineStore } from 'pinia'
import api from '@/api/client'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('growhub_user') || 'null'),
    token: localStorage.getItem('growhub_token') || null,
    loading: false,
    initialized: false,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token && !!state.user,
    isReviewer: (state) => state.user?.role === 'reviewer' || state.user?.role === 'admin',
    isAdmin: (state) => state.user?.role === 'admin',
    userPoints: (state) => state.user?.points || 0,
    userStreak: (state) => state.user?.current_streak || 0,
    userLevel: (state) => state.user?.level_info || { level: 1, name: 'Seedling', progress: 0 },
  },

  actions: {
    async login(nip, password) {
      this.loading = true
      try {
        const res = await api.post('/auth/login', { nip, password })
        this.token = res.data.token
        this.user = res.data.user
        localStorage.setItem('growhub_token', this.token)
        localStorage.setItem('growhub_user', JSON.stringify(this.user))
        return { success: true }
      } catch (err) {
        const msg = err.response?.data?.message || err.response?.data?.errors?.nip?.[0] || 'Login failed. Please check your Employee ID and password.'
        return { success: false, message: msg }
      } finally {
        this.loading = false
      }
    },

    async fetchMe() {
      if (!this.token) return
      try {
        const res = await api.get('/auth/me')
        this.user = res.data
        localStorage.setItem('growhub_user', JSON.stringify(this.user))
      } catch (err) {
        console.error('Fetch me failed', err)
      } finally {
        this.initialized = true
      }
    },

    async logout() {
      try {
        await api.post('/auth/logout')
      } catch (e) {
        // ignore
      } finally {
        this.token = null
        this.user = null
        localStorage.removeItem('growhub_token')
        localStorage.removeItem('growhub_user')
      }
    },

    updateUser(userData) {
      this.user = { ...this.user, ...userData }
      localStorage.setItem('growhub_user', JSON.stringify(this.user))
    },

    mutatePoints(delta) {
      if (!this.user) return
      this.user.points = Math.max(0, (this.user.points || 0) + delta)
      // re-fetch fresh user info in background for level re-calculation
      this.fetchMe()
    },
  },
})
