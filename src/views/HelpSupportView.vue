<template>
  <div class="page help-page">
    <div class="page-header">
      <div>
        <h1 class="page-title">Help & Support</h1>
        <p class="page-subtitle">Help center, gamification rulebook, and support ticketing for retail employees.</p>
      </div>
    </div>

    <!-- Gamification Rules Summary Card -->
    <div class="card card-pad card-dark mb-6">
      <div class="row" style="gap: 12px; margin-bottom: 16px;">
        <Sparkles :size="24" class="text-white" />
        <div>
          <h2 class="card-title text-white">Points & Gamification Guide</h2>
          <p class="small opacity-90">Learn how to maximize your points and accelerate your level progression on GrowHub.</p>
        </div>
      </div>

      <div class="rules-table-wrapper">
        <table class="rules-table">
          <thead>
            <tr>
              <th>Employee Activity</th>
              <th>Points Earned</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>First Daily Activity (Daily Login)</td>
              <td><span class="bold text-green-light">+2 Points</span></td>
              <td>Keeps your active daily streak going</td>
            </tr>
            <tr>
              <td>Submit Content for Review</td>
              <td><span class="bold text-green-light">+5 Points</span></td>
              <td>Awarded once content enters the curation queue</td>
            </tr>
            <tr>
              <td>Content Approved & Published</td>
              <td><span class="bold text-green-light">+25 Points</span></td>
              <td>Validated and published company-wide</td>
            </tr>
            <tr>
              <td>Receive Likes from Colleagues</td>
              <td><span class="bold text-green-light">+2 Points</span></td>
              <td>For each peer who likes your contribution</td>
            </tr>
            <tr>
              <td>Post a Helpful Comment</td>
              <td><span class="bold text-green-light">+1 Point</span></td>
              <td>Encouraging constructive and professional discussion</td>
            </tr>
            <tr>
              <td>Receive Comments on Your Content</td>
              <td><span class="bold text-green-light">+3 Points</span></td>
              <td>When your content triggers active peer discussions</td>
            </tr>
            <tr>
              <td>Complete Learning Center Module</td>
              <td><span class="bold text-green-light">+15 to +35 Points</span></td>
              <td>Based on module duration and complexity</td>
            </tr>
            <tr>
              <td>7-Day Streak Milestone Bonus</td>
              <td><span class="bold text-green-light">+15 Bonus Points</span></td>
              <td>Automatically awarded for every 7 consecutive days active 🔥</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- FAQ Accordion -->
    <div class="card card-pad mb-6">
      <div class="card-head">
        <h2 class="card-title">Frequently Asked Questions (FAQ)</h2>
      </div>

      <div class="faq-list stack">
        <div
          v-for="(faq, i) in faqs"
          :key="i"
          class="faq-item"
          :class="{ 'faq-open': openFaq === i }"
          @click="openFaq = openFaq === i ? null : i"
        >
          <div class="faq-question">
            <span class="badge badge-gray small">{{ faq.group }}</span>
            <span class="bold small text-dark flex-1">{{ faq.q }}</span>
            <ChevronDown :size="18" class="faq-chevron" />
          </div>
          <div v-if="openFaq === i" class="faq-answer">
            {{ faq.a }}
          </div>
        </div>
      </div>
    </div>

    <!-- Submit Ticket & Ticket History Split -->
    <div class="grid grid-2">
      <!-- Ticket Form -->
      <div class="card card-pad">
        <h2 class="card-title mb-2">Submit Support Ticket</h2>
        <p class="small text-2 mb-3">Encountering account or system issues, or need curation support? We're here to help.</p>

        <form @submit.prevent="submitTicket" class="stack">
          <div class="field">
            <label class="label">Issue Subject <span class="text-red">*</span></label>
            <input
              v-model="ticketForm.subject"
              type="text"
              placeholder="e.g. Issue uploading tutorial video..."
              class="input"
              required
            />
          </div>

          <div class="field">
            <label class="label">Category</label>
            <select v-model="ticketForm.category" class="select">
              <option value="general">General</option>
              <option value="account">Account & NIP</option>
              <option value="content">Curation & Content</option>
              <option value="technical">Technical Issue</option>
              <option value="suggestion">Suggestions & Feedback</option>
            </select>
          </div>

          <div class="field">
            <label class="label">Message / Detailed Description <span class="text-red">*</span></label>
            <textarea
              v-model="ticketForm.message"
              rows="4"
              placeholder="Clearly describe the issue you are experiencing..."
              class="textarea"
              required
            ></textarea>
          </div>

          <button type="submit" class="btn btn-primary" :disabled="submittingTicket">
            <span v-if="submittingTicket" class="spinner"></span>
            <Send v-else :size="16" />
            <span>Submit Ticket</span>
          </button>
        </form>
      </div>

      <!-- Ticket History -->
      <div class="card card-pad">
        <h2 class="card-title mb-2">My Ticket History</h2>
        <p class="small text-2 mb-3">Review resolution status of your submitted tickets.</p>

        <div v-if="tickets.length > 0" class="tickets-list stack">
          <div
            v-for="t in tickets"
            :key="t.id"
            class="ticket-item"
          >
            <div class="row-between mb-1">
              <span class="bold small text-dark truncate">{{ t.subject }}</span>
              <span class="badge" :class="t.status === 'resolved' ? 'badge-primary' : 'badge-amber'">
                {{ t.status }}
              </span>
            </div>
            <p class="small text-2 clamp-2">{{ t.message }}</p>
            <div v-if="t.response" class="ticket-response mt-2">
              <span class="small bold text-primary">Team Response:</span>
              <p class="small text-dark mt-1">{{ t.response }}</p>
            </div>
          </div>
        </div>

        <div v-else class="empty py-4">
          <p class="small muted">You haven't submitted any support tickets yet.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Sparkles, ChevronDown, Send } from 'lucide-vue-next'
import api from '@/api/client'
import { useToastStore } from '@/stores/toast'

const toastStore = useToastStore()

const faqs = ref([])
const tickets = ref([])
const openFaq = ref(0)
const submittingTicket = ref(false)

const ticketForm = ref({
  subject: '',
  category: 'general',
  message: '',
})

const fetchSupport = async () => {
  try {
    const [faqRes, tickRes] = await Promise.all([
      api.get('/support/faqs'),
      api.get('/support/tickets'),
    ])
    faqs.value = faqRes.data
    tickets.value = tickRes.data
  } catch (err) {
    console.error('Failed support data', err)
  }
}

const submitTicket = async () => {
  if (!ticketForm.value.subject || !ticketForm.value.message) return
  submittingTicket.value = true
  try {
    await api.post('/support/tickets', ticketForm.value)
    toastStore.success('Support ticket submitted successfully!')
    ticketForm.value = { subject: '', category: 'general', message: '' }
    await fetchSupport()
  } catch (err) {
    toastStore.error('Failed to submit ticket.')
  } finally {
    submittingTicket.value = false
  }
}

onMounted(() => {
  fetchSupport()
})
</script>

<style scoped src="@/styles/features/help-support.css"></style>
