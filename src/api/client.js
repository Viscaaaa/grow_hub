import axios from 'axios'

const defaultBaseURL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api'

const api = axios.create({
  baseURL: defaultBaseURL,
  headers: {
    'Accept': 'application/json',
  },
})

// Attach Bearer token if present
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('growhub_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Handle network error (auto-port failover between 8000 and 8001) & unauthorized responses
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    // If backend is running on alternate port (8000 vs 8001) and request failed to connect
    const config = error.config
    if (!error.response && config && !config._retried) {
      config._retried = true
      const currentURL = config.baseURL || api.defaults.baseURL || ''
      const altURL = currentURL.includes('8001')
        ? currentURL.replace('8001', '8000')
        : currentURL.replace('8000', '8001')

      if (altURL !== currentURL) {
        config.baseURL = altURL
        api.defaults.baseURL = altURL
        return api(config)
      }
    }

    if (error.response && error.response.status === 401) {
      localStorage.removeItem('growhub_token')
      localStorage.removeItem('growhub_user')
      if (window.location.pathname !== '/login') {
        window.location.href = '/login'
      }
    }
    return Promise.reject(error)
  }
)

export default api
