<template>
  <div class="min-h-screen flex flex-col items-center justify-center gap-6 px-4 -my-8">
    <!-- Title -->
    <div class="text-center">
      <h1 class="title-heading text-2xl md:text-3xl font-bold mb-2">
        <span class="title-main">Daily</span>
        <span class="title-sub">Vocabit</span>
      </h1>
      <p class="subtitle">
        <template v-if="step === 'verify'">認証コードを入力</template>
        <template v-else>{{ isLogin ? 'アカウントにログイン' : '新しいアカウントを作成' }}</template>
      </p>
    </div>

    <!-- Auth Form Card -->
    <div class="auth-card">
      <!-- Verification Code Step -->
      <template v-if="step === 'verify'">
        <div class="verify-header">
          <button class="back-button" @click="step = 'form'">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
          </button>
          <p class="verify-email">{{ auth.pendingEmail }}</p>
        </div>

        <p class="verify-message">
          上記のメールアドレスに6桁の認証コードを送信しました。<br>
          コードを入力して登録を完了してください。
        </p>

        <!-- Error Message -->
        <div v-if="auth.error" class="error-message">
          {{ auth.error }}
        </div>

        <form @submit.prevent="handleVerifyCode" class="auth-form">
          <div class="form-group">
            <label for="verification-code">認証コード</label>
            <input
              id="verification-code"
              v-model="verificationCode"
              type="text"
              inputmode="numeric"
              pattern="[0-9]*"
              maxlength="6"
              placeholder="000000"
              class="code-input"
              required
            />
          </div>
          <button type="submit" class="submit-button" :disabled="auth.isLoading || verificationCode.length !== 6">
            {{ auth.isLoading ? '確認中...' : '登録を完了' }}
          </button>
        </form>

        <button
          class="resend-button"
          @click="handleResendCode"
          :disabled="auth.isLoading || resendCooldown > 0"
        >
          {{ resendCooldown > 0 ? `再送信まで ${resendCooldown}秒` : 'コードを再送信' }}
        </button>
      </template>

      <!-- Login / Register Form -->
      <template v-else>
        <!-- Tab Switcher -->
        <div class="tab-switcher">
          <button
            class="tab-button"
            :class="{ active: isLogin }"
            @click="isLogin = true"
          >
            ログイン
          </button>
          <button
            class="tab-button"
            :class="{ active: !isLogin }"
            @click="isLogin = false"
          >
            新規登録
          </button>
        </div>

        <!-- Error Message -->
        <div v-if="auth.error" class="error-message">
          {{ auth.error }}
        </div>

        <!-- Login Form -->
        <form v-if="isLogin" @submit.prevent="handleLogin" class="auth-form">
          <div class="form-group">
            <label for="login-email">メールアドレス</label>
            <input
              id="login-email"
              v-model="loginForm.email"
              type="email"
              placeholder="example@email.com"
              required
            />
          </div>
          <div class="form-group">
            <label for="login-password">パスワード</label>
            <input
              id="login-password"
              v-model="loginForm.password"
              type="password"
              placeholder="********"
              required
            />
          </div>
          <button type="submit" class="submit-button" :disabled="auth.isLoading">
            {{ auth.isLoading ? 'ログイン中...' : 'ログイン' }}
          </button>
        </form>

        <!-- Register Form -->
        <form v-else @submit.prevent="handleRegister" class="auth-form">
          <div class="form-group">
            <label for="register-name">ユーザー名</label>
            <input
              id="register-name"
              v-model="registerForm.name"
              type="text"
              placeholder="ユーザー名"
              required
            />
          </div>
          <div class="form-group">
            <label for="register-email">メールアドレス</label>
            <input
              id="register-email"
              v-model="registerForm.email"
              type="email"
              placeholder="example@email.com"
              required
            />
          </div>
          <div class="form-group">
            <label for="register-password">パスワード</label>
            <input
              id="register-password"
              v-model="registerForm.password"
              type="password"
              placeholder="8文字以上"
              minlength="8"
              required
            />
          </div>
          <div class="form-group">
            <label for="register-password-confirm">パスワード（確認）</label>
            <input
              id="register-password-confirm"
              v-model="registerForm.passwordConfirmation"
              type="password"
              placeholder="パスワードを再入力"
              required
            />
          </div>
          <button type="submit" class="submit-button" :disabled="auth.isLoading">
            {{ auth.isLoading ? '送信中...' : '認証コードを送信' }}
          </button>
        </form>

        <!-- Divider -->
        <div class="divider">
          <span>または</span>
        </div>

        <!-- Google Login Button -->
        <button class="google-button" @click="handleGoogleLogin" :disabled="auth.isLoading || isGoogleLoading">
          <template v-if="isGoogleLoading">
            <span class="google-loading-spinner"></span>
            認証中...
          </template>
          <template v-else>
            <svg class="google-icon" viewBox="0 0 24 24">
              <path
                fill="#4285F4"
                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
              />
              <path
                fill="#34A853"
                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
              />
              <path
                fill="#FBBC05"
                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
              />
              <path
                fill="#EA4335"
                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
              />
            </svg>
            Googleでログイン
          </template>
        </button>
      </template>
    </div>

    <!-- Skip Login Link -->
    <button class="skip-link" @click="skipToHome">
      ログインせずに始める
    </button>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const isLogin = ref(true)
const step = ref('form') // 'form' or 'verify'
const verificationCode = ref('')
const resendCooldown = ref(0)
const isGoogleLoading = ref(false)
const googleClientId = import.meta.env.VITE_GOOGLE_CLIENT_ID
let cooldownInterval = null

const loginForm = reactive({
  email: '',
  password: '',
})

const registerForm = reactive({
  name: '',
  email: '',
  password: '',
  passwordConfirmation: '',
})

async function handleLogin() {
  const result = await auth.login(loginForm.email, loginForm.password)
  if (result.success) {
    router.push({ name: 'Title' })
  }
}

async function handleRegister() {
  if (registerForm.password !== registerForm.passwordConfirmation) {
    auth.error = 'パスワードが一致しません'
    return
  }

  const result = await auth.sendVerificationCode(
    registerForm.name,
    registerForm.email,
    registerForm.password,
    registerForm.passwordConfirmation
  )

  if (result.success) {
    step.value = 'verify'
    startResendCooldown()
  }
}

async function handleVerifyCode() {
  const result = await auth.verifyCode(verificationCode.value)
  if (result.success) {
    router.push({ name: 'Title' })
  }
}

async function handleResendCode() {
  const result = await auth.resendVerificationCode()
  if (result.success) {
    startResendCooldown()
  }
}

function startResendCooldown() {
  resendCooldown.value = 60
  if (cooldownInterval) clearInterval(cooldownInterval)
  cooldownInterval = setInterval(() => {
    resendCooldown.value--
    if (resendCooldown.value <= 0) {
      clearInterval(cooldownInterval)
    }
  }, 1000)
}

async function handleGoogleLogin() {
  if (!googleClientId) {
    auth.error = 'Google認証が設定されていません。管理者にお問い合わせください。'
    return
  }

  if (!window.google) {
    auth.error = 'Google認証の読み込みに失敗しました。ページを再読み込みしてください。'
    return
  }

  isGoogleLoading.value = true
  auth.error = null

  try {
    // Google Identity Services を使用してログイン
    window.google.accounts.id.initialize({
      client_id: googleClientId,
      callback: handleGoogleCallback,
      auto_select: false,
      cancel_on_tap_outside: true,
    })

    // ワンタップUIまたはポップアップを表示
    window.google.accounts.id.prompt((notification) => {
      if (notification.isNotDisplayed() || notification.isSkippedMoment()) {
        // ワンタップが表示されない場合、ポップアップを使用
        window.google.accounts.oauth2.initTokenClient({
          client_id: googleClientId,
          scope: 'email profile',
          callback: handleGoogleTokenResponse,
        }).requestAccessToken()
      }
    })
  } catch (error) {
    console.error('Google login error:', error)
    auth.error = 'Google認証に失敗しました。もう一度お試しください。'
    isGoogleLoading.value = false
  }
}

async function handleGoogleCallback(response) {
  try {
    // JWT トークンをデコードしてユーザー情報を取得
    const payload = JSON.parse(atob(response.credential.split('.')[1]))

    const result = await auth.googleLogin({
      google_id: payload.sub,
      email: payload.email,
      name: payload.name,
      avatar: payload.picture,
    })

    if (result.success) {
      router.push({ name: 'Title' })
    }
  } catch (error) {
    console.error('Google callback error:', error)
    auth.error = 'Google認証に失敗しました。もう一度お試しください。'
  } finally {
    isGoogleLoading.value = false
  }
}

async function handleGoogleTokenResponse(tokenResponse) {
  try {
    // アクセストークンを使用してユーザー情報を取得
    const userInfoResponse = await fetch('https://www.googleapis.com/oauth2/v3/userinfo', {
      headers: { Authorization: `Bearer ${tokenResponse.access_token}` },
    })
    const userInfo = await userInfoResponse.json()

    const result = await auth.googleLogin({
      google_id: userInfo.sub,
      email: userInfo.email,
      name: userInfo.name,
      avatar: userInfo.picture,
    })

    if (result.success) {
      router.push({ name: 'Title' })
    }
  } catch (error) {
    console.error('Google token response error:', error)
    auth.error = 'Google認証に失敗しました。もう一度お試しください。'
  } finally {
    isGoogleLoading.value = false
  }
}

function skipToHome() {
  router.push({ name: 'Title' })
}

onUnmounted(() => {
  if (cooldownInterval) clearInterval(cooldownInterval)
})
</script>

<style scoped>
/* Title Styling */
.title-heading {
  font-family: 'M PLUS Rounded 1c', 'Hiragino Maru Gothic ProN', 'ヒラギノ丸ゴ ProN W4', sans-serif;
  font-weight: 800;
  letter-spacing: 0.05em;
}

.title-main {
  background: linear-gradient(135deg, #5b7a9f 0%, #738ba8 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.title-sub {
  background: linear-gradient(135deg, #b08968 0%, #c9a68a 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  font-weight: 700;
  margin-left: 0.5rem;
}

.subtitle {
  color: #64748b;
  font-size: 0.875rem;
}

/* Auth Card */
.auth-card {
  width: 100%;
  max-width: 24rem;
  background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%);
  border-radius: 1rem;
  padding: 1.5rem;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
  border: 1px solid #e2e8f0;
}

/* Verify Header */
.verify-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.back-button {
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f1f5f9;
  border: none;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: background 0.2s ease;
}

.back-button:hover {
  background: #e2e8f0;
}

.back-button svg {
  width: 1.25rem;
  height: 1.25rem;
  color: #64748b;
}

.verify-email {
  font-size: 0.875rem;
  font-weight: 500;
  color: #334155;
}

.verify-message {
  font-size: 0.8125rem;
  color: #64748b;
  line-height: 1.5;
  margin-bottom: 1.5rem;
}

/* Code Input */
.code-input {
  font-size: 1.5rem !important;
  font-weight: 600;
  letter-spacing: 0.5rem;
  text-align: center;
  font-family: 'Courier New', monospace;
}

/* Resend Button */
.resend-button {
  display: block;
  width: 100%;
  margin-top: 1rem;
  padding: 0.5rem;
  font-size: 0.8125rem;
  color: #64748b;
  background: none;
  border: none;
  cursor: pointer;
  text-decoration: underline;
  text-underline-offset: 2px;
}

.resend-button:hover:not(:disabled) {
  color: #475569;
}

.resend-button:disabled {
  color: #94a3b8;
  cursor: not-allowed;
  text-decoration: none;
}

/* Tab Switcher */
.tab-switcher {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
  background: #f1f5f9;
  padding: 0.25rem;
  border-radius: 0.5rem;
}

.tab-button {
  flex: 1;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #64748b;
  background: transparent;
  border: none;
  border-radius: 0.375rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.tab-button.active {
  background: white;
  color: #334155;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

/* Error Message */
.error-message {
  background: #fef2f2;
  color: #dc2626;
  padding: 0.75rem 1rem;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  margin-bottom: 1rem;
  border: 1px solid #fecaca;
}

/* Form */
.auth-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.form-group label {
  font-size: 0.8125rem;
  font-weight: 500;
  color: #475569;
}

.form-group input {
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  background: white;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-group input:focus {
  outline: none;
  border-color: #738ba8;
  box-shadow: 0 0 0 3px rgba(115, 139, 168, 0.15);
}

.form-group input::placeholder {
  color: #94a3b8;
}

/* Submit Button */
.submit-button {
  padding: 0.75rem 1.5rem;
  background: linear-gradient(135deg, #738ba8 0%, #5b7a9f 100%);
  color: white;
  border: none;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  margin-top: 0.5rem;
}

.submit-button:hover:not(:disabled) {
  background: linear-gradient(135deg, #5b7a9f 0%, #4a6785 100%);
  transform: translateY(-1px);
}

.submit-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Divider */
.divider {
  display: flex;
  align-items: center;
  margin: 1.25rem 0;
  color: #94a3b8;
  font-size: 0.75rem;
}

.divider::before,
.divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: #e2e8f0;
}

.divider span {
  padding: 0 0.75rem;
}

/* Google Button */
.google-button {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.625rem 1rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #334155;
  cursor: pointer;
  transition: all 0.2s ease;
}

.google-button:hover:not(:disabled) {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.google-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.google-icon {
  width: 1.125rem;
  height: 1.125rem;
}

.google-loading-spinner {
  width: 1.125rem;
  height: 1.125rem;
  border: 2px solid #e2e8f0;
  border-top-color: #4285F4;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Skip Link */
.skip-link {
  color: #64748b;
  font-size: 0.8125rem;
  background: none;
  border: none;
  cursor: pointer;
  text-decoration: underline;
  text-underline-offset: 2px;
}

.skip-link:hover {
  color: #475569;
}
</style>
