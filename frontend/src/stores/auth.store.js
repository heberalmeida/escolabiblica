import { defineStore } from 'pinia'
import http from '@/api/http'
import router from '@/router'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null
  }),

  getters: {
    isAuthenticated: (s) => !!s.token,
    isAdmin: (s) => s.user?.roles?.includes('admin'),
    isSupervisor: (s) => s.user?.roles?.includes('supervisor')
  },

  actions: {
    async login(email, password) {
      const { data } = await http.post('/login', { email, password })
      this.token = data.token
      this.user = data.user
      localStorage.setItem('token', this.token)
      http.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
      router.push('/admin')
    },

    async fetchUser() {
      if (!this.token) return
      try {
        http.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
        const { data } = await http.get('/auth/check-token')
        this.user = data.user
      } catch (e) {
        this.logout()
      }
    },


    logout() {
      this.token = null
      this.user = null
      localStorage.removeItem('token')
      delete http.defaults.headers.common['Authorization']
      router.push('/admin/login')
    }
  }
})
