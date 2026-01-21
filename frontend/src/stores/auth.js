import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api/client'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('auth_token'))
  const isLoading = ref(false)
  const error = ref(null)
  const pendingEmail = ref(null) // 認証コード送信後のメールアドレス

  const isAuthenticated = computed(() => !!token.value && !!user.value)

  function setAuth(userData, authToken) {
    user.value = userData
    token.value = authToken
    localStorage.setItem('auth_token', authToken)
  }

  function clearAuth() {
    user.value = null
    token.value = null
    localStorage.removeItem('auth_token')
  }

  async function sendVerificationCode(name, email, password, passwordConfirmation) {
    isLoading.value = true
    error.value = null

    try {
      const response = await authApi.sendVerificationCode({
        name,
        email,
        password,
        password_confirmation: passwordConfirmation,
      })

      if (response.success) {
        pendingEmail.value = email
        return { success: true }
      } else {
        error.value = response.message || '認証コードの送信に失敗しました'
        return { success: false, error: error.value }
      }
    } catch (e) {
      error.value = e.message || '認証コードの送信に失敗しました'
      return { success: false, error: error.value }
    } finally {
      isLoading.value = false
    }
  }

  async function verifyCode(code) {
    if (!pendingEmail.value) {
      error.value = 'メールアドレスが見つかりません'
      return { success: false, error: error.value }
    }

    isLoading.value = true
    error.value = null

    try {
      const response = await authApi.verifyCode({
        email: pendingEmail.value,
        code,
      })

      if (response.success) {
        setAuth(response.data.user, response.data.token)
        pendingEmail.value = null
        return { success: true }
      } else {
        error.value = response.message || '認証コードの検証に失敗しました'
        return { success: false, error: error.value }
      }
    } catch (e) {
      error.value = e.message || '認証コードの検証に失敗しました'
      return { success: false, error: error.value }
    } finally {
      isLoading.value = false
    }
  }

  async function resendVerificationCode() {
    if (!pendingEmail.value) {
      error.value = 'メールアドレスが見つかりません'
      return { success: false, error: error.value }
    }

    isLoading.value = true
    error.value = null

    try {
      const response = await authApi.resendVerificationCode({
        email: pendingEmail.value,
      })

      if (response.success) {
        return { success: true }
      } else {
        error.value = response.message || '認証コードの再送信に失敗しました'
        return { success: false, error: error.value }
      }
    } catch (e) {
      error.value = e.message || '認証コードの再送信に失敗しました'
      return { success: false, error: error.value }
    } finally {
      isLoading.value = false
    }
  }

  async function register(name, email, password, passwordConfirmation) {
    isLoading.value = true
    error.value = null

    try {
      const response = await authApi.register({
        name,
        email,
        password,
        password_confirmation: passwordConfirmation,
      })

      if (response.success) {
        setAuth(response.data.user, response.data.token)
        return { success: true }
      } else {
        error.value = response.message || '登録に失敗しました'
        return { success: false, error: error.value }
      }
    } catch (e) {
      error.value = e.message || '登録に失敗しました'
      return { success: false, error: error.value }
    } finally {
      isLoading.value = false
    }
  }

  async function login(email, password) {
    isLoading.value = true
    error.value = null

    try {
      const response = await authApi.login({ email, password })

      if (response.success) {
        setAuth(response.data.user, response.data.token)
        return { success: true }
      } else {
        error.value = response.message || 'ログインに失敗しました'
        return { success: false, error: error.value }
      }
    } catch (e) {
      error.value = e.message || 'ログインに失敗しました'
      return { success: false, error: error.value }
    } finally {
      isLoading.value = false
    }
  }

  async function logout() {
    isLoading.value = true

    try {
      await authApi.logout()
    } catch (e) {
      console.error('Logout error:', e)
    } finally {
      clearAuth()
      isLoading.value = false
    }
  }

  async function fetchUser() {
    if (!token.value) return

    isLoading.value = true

    try {
      const response = await authApi.me()

      if (response.success) {
        user.value = response.data.user
      } else {
        clearAuth()
      }
    } catch (e) {
      console.error('Fetch user error:', e)
      clearAuth()
    } finally {
      isLoading.value = false
    }
  }

  async function googleLogin(googleUserData) {
    isLoading.value = true
    error.value = null

    try {
      const response = await authApi.googleLogin(googleUserData)

      if (response.success) {
        setAuth(response.data.user, response.data.token)
        return { success: true }
      } else {
        error.value = response.message || 'Googleログインに失敗しました'
        return { success: false, error: error.value }
      }
    } catch (e) {
      error.value = e.message || 'Googleログインに失敗しました'
      return { success: false, error: error.value }
    } finally {
      isLoading.value = false
    }
  }

  async function initAuth() {
    if (token.value) {
      await fetchUser()
    }
  }

  return {
    user,
    token,
    isLoading,
    error,
    pendingEmail,
    isAuthenticated,
    sendVerificationCode,
    verifyCode,
    resendVerificationCode,
    register,
    login,
    logout,
    fetchUser,
    googleLogin,
    initAuth,
    clearAuth,
  }
})
