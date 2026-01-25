/**
 * TOEIC Daily API クライアント
 */

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

/**
 * API リクエストのヘルパー関数
 */
async function apiRequest(endpoint, options = {}) {
  const url = `${API_BASE_URL}${endpoint}`

  const { headers: optionHeaders, ...restOptions } = options

  const config = {
    ...restOptions,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...optionHeaders,
    },
  }

  try {
    const response = await fetch(url, config)

    if (!response.ok) {
      const error = await response.json().catch(() => ({ message: 'API Error' }))
      throw new Error(error.message || `HTTP error! status: ${response.status}`)
    }

    return await response.json()
  } catch (error) {
    console.error('API Request Failed:', error)
    throw error
  }
}

/**
 * 問題取得 API
 */
export const questionsApi = {
  /**
   * 今日の問題を取得
   * @param {Object} params - クエリパラメータ
   * @param {string} params.type - 問題タイプ (WORD/IDIOM)
   * @param {number} params.difficulty - 難易度 (1-3)
   * @param {string} params.date - 日付 (YYYY-MM-DD)
   */
  async getDaily(params = {}) {
    const queryParams = new URLSearchParams()

    if (params.type) queryParams.append('type', params.type)
    if (params.difficulty) queryParams.append('difficulty', params.difficulty)
    if (params.date) queryParams.append('date', params.date)

    const queryString = queryParams.toString()
    const endpoint = `/questions/daily${queryString ? `?${queryString}` : ''}`

    return apiRequest(endpoint)
  },

  /**
   * 問題一覧を取得
   */
  async getAll(params = {}) {
    const queryParams = new URLSearchParams(params)
    const queryString = queryParams.toString()
    const endpoint = `/questions${queryString ? `?${queryString}` : ''}`

    return apiRequest(endpoint)
  },

  /**
   * 問題詳細を取得
   * @param {number} id - 問題ID
   */
  async getById(id) {
    return apiRequest(`/questions/${id}`)
  },
}

/**
 * 回答記録 API
 */
export const answersApi = {
  /**
   * 回答を記録
   * @param {Object} data - 回答データ
   * @param {number} data.question_id - 問題ID
   * @param {number} data.selected_index - 選択した選択肢 (0-3)
   * @param {number} data.user_id - ユーザーID (オプション)
   */
  async submit(data) {
    const token = localStorage.getItem('auth_token')
    return apiRequest('/answers', {
      method: 'POST',
      body: JSON.stringify(data),
      headers: token ? { Authorization: `Bearer ${token}` } : {},
    })
  },

  /**
   * 回答履歴を取得
   * @param {number} userId - ユーザーID
   */
  async getHistory(userId) {
    return apiRequest(`/answers/history?user_id=${userId}`)
  },
}

/**
 * 学習進捗 API
 */
export const progressApi = {
  /**
   * 学習進捗を取得
   * @param {Object} params - クエリパラメータ
   */
  async get(params = {}) {
    const queryParams = new URLSearchParams(params)
    const queryString = queryParams.toString()
    const endpoint = `/progress${queryString ? `?${queryString}` : ''}`

    return apiRequest(endpoint)
  },

  /**
   * 学習進捗を保存
   * @param {Object} data - 進捗データ
   */
  async save(data) {
    return apiRequest('/progress', {
      method: 'POST',
      body: JSON.stringify(data),
    })
  },
}

/**
 * 単語・イディオム API
 */
export const vocabulariesApi = {
  /**
   * 単語一覧を取得
   */
  async getAll(params = {}) {
    const queryParams = new URLSearchParams(params)
    const queryString = queryParams.toString()
    const endpoint = `/vocabularies${queryString ? `?${queryString}` : ''}`

    return apiRequest(endpoint)
  },

  /**
   * 単語詳細を取得
   * @param {number} id - 単語ID
   */
  async getById(id) {
    return apiRequest(`/vocabularies/${id}`)
  },
}

/**
 * テーマ API
 */
export const themesApi = {
  /**
   * 今日のテーマを取得
   */
  async getToday() {
    return apiRequest('/themes/today')
  },

  /**
   * 指定日のテーマを取得
   * @param {string} date - 日付 (YYYY-MM-DD)
   */
  async getByDate(date) {
    return apiRequest(`/themes/date?date=${date}`)
  },
}

/**
 * ヘルスチェック API
 */
export const healthApi = {
  async check() {
    return apiRequest('/health')
  },
}

/**
 * 統計 API
 */
export const statsApi = {
  /**
   * ダッシュボード統計を取得
   */
  async getDashboard() {
    const token = localStorage.getItem('auth_token')
    return apiRequest('/stats/dashboard', {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  },

  /**
   * 学習履歴を取得
   * @param {Object} params
   * @param {number} params.page - ページ番号
   * @param {number} params.per_page - 1ページあたりの件数
   */
  async getHistory(params = {}) {
    const token = localStorage.getItem('auth_token')
    const queryParams = new URLSearchParams(params)
    const queryString = queryParams.toString()
    const endpoint = `/stats/history${queryString ? `?${queryString}` : ''}`

    return apiRequest(endpoint, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  },

  /**
   * 日別統計を取得
   * @param {number} days - 過去何日分
   */
  async getDaily(days = 30) {
    const token = localStorage.getItem('auth_token')
    return apiRequest(`/stats/daily?days=${days}`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  },

  /**
   * セッション履歴を取得（10問セット単位）
   * @param {Object} params
   * @param {number} params.page - ページ番号
   * @param {number} params.per_page - 1ページあたりの件数
   */
  async getSessions(params = {}) {
    const token = localStorage.getItem('auth_token')
    const queryParams = new URLSearchParams(params)
    const queryString = queryParams.toString()
    const endpoint = `/stats/sessions${queryString ? `?${queryString}` : ''}`

    return apiRequest(endpoint, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  },

  /**
   * セッション詳細を取得
   * @param {number} sessionId - セッションID
   */
  async getSessionDetail(sessionId) {
    const token = localStorage.getItem('auth_token')
    return apiRequest(`/stats/sessions/${sessionId}`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  },

  /**
   * カレンダーデータを取得
   * @param {number} year - 年
   * @param {number} month - 月
   */
  async getCalendar(year, month) {
    const token = localStorage.getItem('auth_token')
    return apiRequest(`/stats/calendar?year=${year}&month=${month}`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  },
}

/**
 * バッジ API
 */
export const badgeApi = {
  /**
   * 指定月のバッジ一覧を取得（進捗含む）
   * @param {number} year - 年
   * @param {number} month - 月
   */
  async getMonthly(year, month) {
    const token = localStorage.getItem('auth_token')
    return apiRequest(`/badges?year=${year}&month=${month}`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  },

  /**
   * 最近獲得したバッジを取得
   * @param {number} limit - 取得件数
   */
  async getRecent(limit = 5) {
    const token = localStorage.getItem('auth_token')
    return apiRequest(`/badges/recent?limit=${limit}`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  },

  /**
   * 過去の月別バッジ履歴を取得
   */
  async getHistory() {
    const token = localStorage.getItem('auth_token')
    return apiRequest('/badges/history', {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  },
}

/**
 * クイズセッション API
 */
export const quizSessionApi = {
  /**
   * セッションを開始
   * @param {number} difficulty - 難易度 (1: standard, 2: hard)
   */
  async start(difficulty) {
    const token = localStorage.getItem('auth_token')
    return apiRequest('/quiz-sessions/start', {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify({ difficulty }),
    })
  },

  /**
   * セッションを完了
   * @param {number} sessionId - セッションID
   * @param {number} correctCount - 正解数
   */
  async complete(sessionId, correctCount) {
    const token = localStorage.getItem('auth_token')
    return apiRequest(`/quiz-sessions/${sessionId}/complete`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify({ correct_count: correctCount }),
    })
  },

  /**
   * 現在のセッションを取得
   */
  async getCurrent() {
    const token = localStorage.getItem('auth_token')
    return apiRequest('/quiz-sessions/current', {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  },
}

/**
 * 認証 API
 */
export const authApi = {
  /**
   * 認証コードを送信（登録の第一段階）
   * @param {Object} data - 登録データ
   * @param {string} data.name - ユーザー名
   * @param {string} data.email - メールアドレス
   * @param {string} data.password - パスワード
   * @param {string} data.password_confirmation - パスワード確認
   */
  async sendVerificationCode(data) {
    return apiRequest('/auth/send-code', {
      method: 'POST',
      body: JSON.stringify(data),
    })
  },

  /**
   * 認証コードを検証して登録完了
   * @param {Object} data
   * @param {string} data.email - メールアドレス
   * @param {string} data.code - 6桁の認証コード
   */
  async verifyCode(data) {
    return apiRequest('/auth/verify-code', {
      method: 'POST',
      body: JSON.stringify(data),
    })
  },

  /**
   * 認証コードを再送信
   * @param {Object} data
   * @param {string} data.email - メールアドレス
   */
  async resendVerificationCode(data) {
    return apiRequest('/auth/resend-code', {
      method: 'POST',
      body: JSON.stringify(data),
    })
  },

  /**
   * ユーザー登録（従来方式）
   * @param {Object} data - 登録データ
   * @param {string} data.name - ユーザー名
   * @param {string} data.email - メールアドレス
   * @param {string} data.password - パスワード
   * @param {string} data.password_confirmation - パスワード確認
   */
  async register(data) {
    return apiRequest('/auth/register', {
      method: 'POST',
      body: JSON.stringify(data),
    })
  },

  /**
   * ログイン
   * @param {Object} data - ログインデータ
   * @param {string} data.email - メールアドレス
   * @param {string} data.password - パスワード
   */
  async login(data) {
    return apiRequest('/auth/login', {
      method: 'POST',
      body: JSON.stringify(data),
    })
  },

  /**
   * ログアウト
   */
  async logout() {
    const token = localStorage.getItem('auth_token')
    return apiRequest('/auth/logout', {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  },

  /**
   * 現在のユーザー情報を取得
   */
  async me() {
    const token = localStorage.getItem('auth_token')
    return apiRequest('/auth/me', {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  },

  /**
   * Google OAuth コールバック
   * @param {Object} data - Googleユーザーデータ
   * @param {string} data.google_id - Google ID
   * @param {string} data.email - メールアドレス
   * @param {string} data.name - ユーザー名
   * @param {string} data.avatar - アバター画像URL
   */
  async googleLogin(data) {
    return apiRequest('/auth/google', {
      method: 'POST',
      body: JSON.stringify(data),
    })
  },
}

/**
 * 管理者 API
 */
export const adminApi = {
  /**
   * ダッシュボード統計を取得
   */
  async getDashboard() {
    const token = localStorage.getItem('auth_token')
    return apiRequest('/admin/dashboard', {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  },

  /**
   * 日ごとの問題一覧を取得
   * @param {string} date - 日付 (YYYY-MM-DD)
   * @param {number} difficulty - 難易度
   */
  async getQuestions(date, difficulty = null) {
    const token = localStorage.getItem('auth_token')
    const params = new URLSearchParams({ date })
    if (difficulty) params.append('difficulty', difficulty)

    return apiRequest(`/admin/questions?${params}`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  },

  /**
   * 問題の日付一覧を取得
   */
  async getQuestionDates() {
    const token = localStorage.getItem('auth_token')
    return apiRequest('/admin/questions/dates', {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  },

  /**
   * 語彙一覧を取得
   * @param {Object} params - クエリパラメータ
   */
  async getVocabularies(params = {}) {
    const token = localStorage.getItem('auth_token')
    const queryParams = new URLSearchParams(params)

    return apiRequest(`/admin/vocabularies?${queryParams}`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  },

  /**
   * 語彙詳細を取得
   * @param {number} id - 語彙ID
   */
  async getVocabulary(id) {
    const token = localStorage.getItem('auth_token')
    return apiRequest(`/admin/vocabularies/${id}`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  },

  /**
   * 語彙を作成
   * @param {Object} data - 語彙データ
   */
  async createVocabulary(data) {
    const token = localStorage.getItem('auth_token')
    return apiRequest('/admin/vocabularies', {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify(data),
    })
  },

  /**
   * 語彙を更新
   * @param {number} id - 語彙ID
   * @param {Object} data - 語彙データ
   */
  async updateVocabulary(id, data) {
    const token = localStorage.getItem('auth_token')
    return apiRequest(`/admin/vocabularies/${id}`, {
      method: 'PUT',
      headers: {
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify(data),
    })
  },

  /**
   * 語彙を削除
   * @param {number} id - 語彙ID
   */
  async deleteVocabulary(id) {
    const token = localStorage.getItem('auth_token')
    return apiRequest(`/admin/vocabularies/${id}`, {
      method: 'DELETE',
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  },
}
