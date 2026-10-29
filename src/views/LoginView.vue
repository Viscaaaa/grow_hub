<template>
  <div class="login-page">
    <div class="login-wrapper">
      <!-- Left side: Branding & Gamification Showpiece -->
      <div class="login-hero">
        <div class="hero-top">
          <div class="brand-badge">
            <Sprout :size="24" class="text-white" />
            <span class="brand-badge-name">Grow<span class="text-green-light">Hub</span></span>
          </div>
          <span class="hero-tagline">Retail Knowledge & Engagement System</span>
        </div>

        <div class="hero-center">
          <h1 class="hero-heading">
            Grow Together,<br />
            Share Knowledge.
          </h1>
          <p class="hero-subtext">
            Retail collaboration and gamification platform where your ideas, best practices, and experiences are rewarded with points, streaks, and recognition.
          </p>

          <!-- Gamification features list -->
          <div class="hero-features">
            <div class="feature-item">
              <div class="feature-icon bg-amber">
                <Sparkles :size="18" />
              </div>
              <div class="feature-text">
                <span class="bold">Points & Rewards</span>
                <span class="small opacity-80">Earn points from every content post, discussion, and course</span>
              </div>
            </div>

            <div class="feature-item">
              <div class="feature-icon bg-orange">
                <Flame :size="18" />
              </div>
              <div class="feature-text">
                <span class="bold">Daily Streak</span>
                <span class="small opacity-80">Maintain daily consistency to unlock streak bonuses</span>
              </div>
            </div>

            <div class="feature-item">
              <div class="feature-icon bg-green">
                <Award :size="18" />
              </div>
              <div class="feature-text">
                <span class="bold">Levels & Leaderboard</span>
                <span class="small opacity-80">Advance from Seedling to Evergreen and lead company rankings</span>
              </div>
            </div>
          </div>
        </div>

        <div class="hero-bottom">
          <div class="security-badge">
            <ShieldCheck :size="16" />
            <span>Authorized Retail Employees System</span>
          </div>
        </div>
      </div>

      <!-- Right side: Login Form / First-Time Password Setup Form -->
      <div class="login-form-container">
        <!-- FIRST LOGIN PASSWORD SETUP -->
        <div v-if="isFirstLogin" class="first-login-wrapper">
          <div class="form-header">
            <div class="welcome-label label-security">
              <KeyRound :size="13" />
              <span>Security & Activation</span>
            </div>
            <h2 class="form-title">Set New Password</h2>
            <p class="form-subtitle">
              Untuk keamanan akun retail Anda, silakan ubah password sementara Anda sebelum melanjutkan ke GrowHub.
            </p>
          </div>

          <!-- Logged in User Identification Card -->
          <div class="user-pill-card mb-3">
            <div class="user-pill-avatar">
              <User :size="18" />
            </div>
            <div class="user-pill-info">
              <div class="user-pill-name bold">{{ authStore.user?.name }}</div>
              <div class="user-pill-meta small muted">NIP: {{ authStore.user?.nip }} • <span class="capitalize">{{ authStore.user?.role || 'employee' }}</span></div>
            </div>
          </div>

          <!-- Security Policy Note -->
          <div class="security-info-box mb-3">
            <ShieldCheck :size="16" class="text-primary flex-shrink-0" />
            <span class="small">
              Setelah password baru dibuat, reset password berikutnya <strong>hanya dapat dilakukan oleh Administrator</strong>.
            </span>
          </div>

          <form @submit.prevent="handleFirstChangePassword" class="login-form stack">
            <!-- Error alert -->
            <div v-if="changeErrorMessage" class="error-alert">
              <AlertCircle :size="18" class="text-red" />
              <span>{{ changeErrorMessage }}</span>
            </div>

            <!-- New Password -->
            <div class="field">
              <label class="label">Password Baru <span class="text-red">*</span></label>
              <div class="input-icon">
                <Lock :size="18" />
                <input
                  v-model="newPassword"
                  :type="showNewPassword ? 'text' : 'password'"
                  placeholder="Minimal 8 karakter"
                  class="input"
                  required
                  minlength="8"
                  autocomplete="new-password"
                />
                <button
                  type="button"
                  class="btn-eye"
                  @click="showNewPassword = !showNewPassword"
                  aria-label="Toggle password"
                >
                  <EyeOff v-if="showNewPassword" :size="18" />
                  <Eye v-else :size="18" />
                </button>
              </div>
              <span class="hint">Minimal 8 karakter & tidak boleh sama dengan NIP default</span>
            </div>

            <!-- Confirm Password -->
            <div class="field">
              <label class="label">Konfirmasi Password Baru <span class="text-red">*</span></label>
              <div class="input-icon">
                <Lock :size="18" />
                <input
                  v-model="confirmPassword"
                  :type="showConfirmPassword ? 'text' : 'password'"
                  placeholder="Ulangi password baru"
                  class="input"
                  required
                  minlength="8"
                  autocomplete="new-password"
                />
                <button
                  type="button"
                  class="btn-eye"
                  @click="showConfirmPassword = !showConfirmPassword"
                  aria-label="Toggle password"
                >
                  <EyeOff v-if="showConfirmPassword" :size="18" />
                  <Eye v-else :size="18" />
                </button>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="stack mt-2">
              <button type="submit" class="btn btn-primary btn-lg btn-block" :disabled="changeLoading">
                <span v-if="changeLoading" class="spinner"></span>
                <span v-else>Simpan & Masuk ke GrowHub</span>
                <ArrowRight v-if="!changeLoading" :size="18" />
              </button>

              <button
                type="button"
                class="btn btn-outline btn-block"
                :disabled="changeLoading"
                @click="cancelFirstLogin"
              >
                Batal / Ganti Akun
              </button>
            </div>
          </form>
        </div>

        <!-- STANDARD SIGN IN FORM -->
        <div v-else>
          <div class="form-header">
            <div class="welcome-label">Welcome Back</div>
            <h2 class="form-title">Sign in to GrowHub</h2>
            <p class="form-subtitle">Use your 9-10 digit Employee ID (NIP).</p>
          </div>

          <form @submit.prevent="handleSubmit" class="login-form stack">
            <!-- Error alert -->
            <div v-if="errorMessage" class="error-alert">
              <AlertCircle :size="18" class="text-red" />
              <span>{{ errorMessage }}</span>
            </div>

            <!-- NIP Input -->
            <div class="field">
              <label class="label">Employee ID (NIP)</label>
              <div class="input-icon">
                <User :size="18" />
                <input
                  v-model="nip"
                  type="text"
                  maxlength="10"
                  placeholder="e.g. 202301001"
                  class="input"
                  required
                  autocomplete="username"
                />
              </div>
              <span class="hint">Employee ID consists of 9 to 10 numeric digits</span>
            </div>

            <!-- Password Input -->
            <div class="field">
              <label class="label">Password</label>
              <div class="input-icon">
                <Lock :size="18" />
                <input
                  v-model="password"
                  :type="showPassword ? 'text' : 'password'"
                  placeholder="Enter password"
                  class="input"
                  required
                  autocomplete="current-password"
                />
                <button
                  type="button"
                  class="btn-eye"
                  @click="showPassword = !showPassword"
                  aria-label="Toggle password"
                >
                  <EyeOff v-if="showPassword" :size="18" />
                  <Eye v-else :size="18" />
                </button>
              </div>
            
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary btn-lg btn-block mt-2" :disabled="loading">
              <span v-if="loading" class="spinner"></span>
              <span v-else>Sign In</span>
              <ArrowRight v-if="!loading" :size="18" />
            </button>
          </form>

          <!-- Quick Demo Accounts (for effortless testing of different roles) -->
          <div class="demo-accounts-card">
            <div class="demo-title">
              <Sparkles :size="14" class="text-primary" />
              <span>Quick Demo Accounts (Click to Fill):</span>
            </div>
            <div class="demo-chips">
              <button
                v-for="acc in demoAccounts"
                :key="acc.nip"
                class="demo-chip"
                @click="fillDemo(acc)"
                type="button"
              >
                <div class="demo-chip-role" :class="`role-${acc.role}`">{{ acc.role }}</div>
                <div class="demo-chip-text">
                  <span class="bold">{{ acc.name }}</span>
                  <span class="muted small">ID: {{ acc.nip }}</span>
                </div>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import {
  Sprout, Sparkles, Flame, Award, ShieldCheck, User, Lock,
  Eye, EyeOff, ArrowRight, AlertCircle, KeyRound
} from 'lucide-vue-next'
import api from '@/api/client'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'

const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()

const nip = ref('202301001')
const password = ref('202301001')
const showPassword = ref(false)
const loading = ref(false)
const errorMessage = ref('')

// First login password setup state
const isFirstLogin = ref(false)
const newPassword = ref('')
const confirmPassword = ref('')
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)
const changeLoading = ref(false)
const changeErrorMessage = ref('')

onMounted(() => {
  if (authStore.isAuthenticated && authStore.user?.must_change_password) {
    isFirstLogin.value = true
  }
})

const demoAccounts = [
  { nip: '202301001', name: 'Andi Saputra', role: 'employee', dept: 'Store Operations' },
  { nip: '1000000002', name: 'Rina Kartika', role: 'reviewer', dept: 'Human Capital' },
  { nip: '1000000001', name: 'Admin GrowHub', role: 'admin', dept: 'HC Administrator' },
]

const fillDemo = (acc) => {
  nip.value = acc.nip
  password.value = acc.nip
  errorMessage.value = ''
}

const handleSubmit = async () => {
  if (!/^\d{9,10}$/.test(nip.value)) {
    errorMessage.value = 'Employee ID must consist of 9 to 10 numeric digits.'
    return
  }

  loading.value = true
  errorMessage.value = ''

  const res = await authStore.login(nip.value, password.value)
  loading.value = false

  if (res.success) {
    if (authStore.user?.must_change_password) {
      isFirstLogin.value = true
      newPassword.value = ''
      confirmPassword.value = ''
      changeErrorMessage.value = ''
      toastStore.info('Silakan buat password baru untuk mengaktifkan akun Anda.')
    } else {
      toastStore.success(`Welcome back, ${authStore.user?.name}!`)
      router.push('/')
    }
  } else {
    errorMessage.value = res.message
  }
}

const handleFirstChangePassword = async () => {
  changeErrorMessage.value = ''

  if (newPassword.value.length < 8) {
    changeErrorMessage.value = 'Password baru minimal 8 karakter.'
    return
  }

  if (newPassword.value !== confirmPassword.value) {
    changeErrorMessage.value = 'Konfirmasi password baru tidak cocok.'
    return
  }

  if (newPassword.value === authStore.user?.nip) {
    changeErrorMessage.value = 'Password baru tidak boleh sama dengan NIP default.'
    return
  }

  changeLoading.value = true
  try {
    const res = await api.post('/auth/first-change-password', {
      password: newPassword.value,
      password_confirmation: confirmPassword.value,
    })

    authStore.updateUser(res.data.user)
    toastStore.success('Password berhasil diperbarui! Selamat datang di GrowHub.')
    isFirstLogin.value = false
    router.push('/')
  } catch (err) {
    changeErrorMessage.value =
      err.response?.data?.errors?.password?.[0] ||
      err.response?.data?.message ||
      'Gagal memperbarui password.'
  } finally {
    changeLoading.value = false
  }
}

const cancelFirstLogin = async () => {
  await authStore.logout()
  isFirstLogin.value = false
  newPassword.value = ''
  confirmPassword.value = ''
  changeErrorMessage.value = ''
  errorMessage.value = ''
}
</script>

<style scoped src="@/styles/features/login.css"></style>
