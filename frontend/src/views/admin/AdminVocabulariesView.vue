<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { adminApi } from '@/api/client'

const router = useRouter()
const auth = useAuthStore()

const vocabularies = ref([])
const pagination = ref({})
const search = ref('')
const filterType = ref('')
const filterDifficulty = ref('')
const currentPage = ref(1)
const isLoading = ref(true)
const error = ref(null)

// 編集モーダル
const showModal = ref(false)
const modalMode = ref('create') // 'create' or 'edit'
const editingVocab = ref(null)
const formData = ref({
  word: '',
  type: 'WORD',
  difficulty: 1,
  meaning: '',
  part_of_speech: '',
  example_sentence: '',
  synonym: '',
  antonym: '',
  frequency: 3,
  tags: '',
})
const formErrors = ref({})
const isSaving = ref(false)

async function loadVocabularies() {
  isLoading.value = true
  error.value = null

  try {
    const params = {
      page: currentPage.value,
      per_page: 20,
    }
    if (search.value) params.search = search.value
    if (filterType.value) params.type = filterType.value
    if (filterDifficulty.value) params.difficulty = filterDifficulty.value

    const response = await adminApi.getVocabularies(params)
    vocabularies.value = response.data
    pagination.value = {
      currentPage: response.current_page,
      lastPage: response.last_page,
      total: response.total,
    }
  } catch (e) {
    error.value = e.message
  } finally {
    isLoading.value = false
  }
}

function openCreateModal() {
  modalMode.value = 'create'
  editingVocab.value = null
  formData.value = {
    word: '',
    type: 'WORD',
    difficulty: 1,
    meaning: '',
    part_of_speech: '',
    example_sentence: '',
    synonym: '',
    antonym: '',
    frequency: 3,
    tags: '',
  }
  formErrors.value = {}
  showModal.value = true
}

function openEditModal(vocab) {
  modalMode.value = 'edit'
  editingVocab.value = vocab
  formData.value = {
    word: vocab.word,
    type: vocab.type,
    difficulty: vocab.difficulty,
    meaning: vocab.meaning,
    part_of_speech: vocab.part_of_speech || '',
    example_sentence: vocab.example_sentence || '',
    synonym: vocab.synonym || '',
    antonym: vocab.antonym || '',
    frequency: vocab.frequency || 3,
    tags: vocab.tags || '',
  }
  formErrors.value = {}
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  editingVocab.value = null
}

async function saveVocabulary() {
  isSaving.value = true
  formErrors.value = {}

  try {
    if (modalMode.value === 'create') {
      await adminApi.createVocabulary(formData.value)
    } else {
      await adminApi.updateVocabulary(editingVocab.value.id, formData.value)
    }
    closeModal()
    loadVocabularies()
  } catch (e) {
    if (e.message.includes('errors')) {
      try {
        const parsed = JSON.parse(e.message)
        formErrors.value = parsed.errors
      } catch {
        formErrors.value = { general: e.message }
      }
    } else {
      formErrors.value = { general: e.message }
    }
  } finally {
    isSaving.value = false
  }
}

async function deleteVocabulary(vocab) {
  if (!confirm(`「${vocab.word}」を削除しますか？`)) return

  try {
    await adminApi.deleteVocabulary(vocab.id)
    loadVocabularies()
  } catch (e) {
    alert(e.message)
  }
}

function getDifficultyLabel(difficulty) {
  const labels = { 1: '基礎', 2: '上級', 3: '超上級' }
  return labels[difficulty] || difficulty
}

function goToPage(page) {
  currentPage.value = page
}

let searchTimeout = null
watch(search, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    currentPage.value = 1
    loadVocabularies()
  }, 300)
})

watch([filterType, filterDifficulty], () => {
  currentPage.value = 1
  loadVocabularies()
})

watch(currentPage, () => {
  loadVocabularies()
})

onMounted(() => {
  if (!auth.isAdmin) {
    router.push({ name: 'Title' })
    return
  }
  loadVocabularies()
})
</script>

<template>
  <div class="admin-vocabularies">
    <header class="page-header">
      <button class="back-btn" @click="router.push({ name: 'AdminDashboard' })">
        &larr; 戻る
      </button>
      <h1>語彙管理</h1>
      <button class="create-btn" @click="openCreateModal">+ 新規作成</button>
    </header>

    <div class="filters">
      <div class="filter-group search">
        <input
          v-model="search"
          type="text"
          placeholder="単語・意味で検索..."
        />
      </div>
      <div class="filter-group">
        <select v-model="filterType">
          <option value="">すべてのタイプ</option>
          <option value="WORD">単語</option>
          <option value="IDIOM">イディオム</option>
        </select>
      </div>
      <div class="filter-group">
        <select v-model="filterDifficulty">
          <option value="">すべての難易度</option>
          <option value="1">基礎</option>
          <option value="2">上級</option>
          <option value="3">超上級</option>
        </select>
      </div>
    </div>

    <div v-if="pagination.total" class="total-count">
      {{ pagination.total }}件中 {{ (pagination.currentPage - 1) * 20 + 1 }} - {{ Math.min(pagination.currentPage * 20, pagination.total) }}件
    </div>

    <div v-if="isLoading" class="loading">読み込み中...</div>

    <div v-else-if="error" class="error">{{ error }}</div>

    <div v-else class="vocabularies-list">
      <table class="vocab-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>単語/イディオム</th>
            <th>タイプ</th>
            <th>難易度</th>
            <th>意味</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="vocab in vocabularies" :key="vocab.id">
            <td>{{ vocab.id }}</td>
            <td class="word-cell">{{ vocab.word }}</td>
            <td>
              <span class="type-badge" :class="vocab.type.toLowerCase()">
                {{ vocab.type === 'WORD' ? '単語' : 'イディオム' }}
              </span>
            </td>
            <td>
              <span class="difficulty-badge" :class="'diff-' + vocab.difficulty">
                {{ getDifficultyLabel(vocab.difficulty) }}
              </span>
            </td>
            <td class="meaning-cell">{{ vocab.meaning }}</td>
            <td class="action-cell">
              <button class="edit-btn" @click="openEditModal(vocab)">編集</button>
              <button class="delete-btn" @click="deleteVocabulary(vocab)">削除</button>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="pagination.lastPage > 1" class="pagination">
        <button
          :disabled="pagination.currentPage <= 1"
          @click="goToPage(pagination.currentPage - 1)"
        >
          前へ
        </button>
        <span class="page-info">
          {{ pagination.currentPage }} / {{ pagination.lastPage }}
        </span>
        <button
          :disabled="pagination.currentPage >= pagination.lastPage"
          @click="goToPage(pagination.currentPage + 1)"
        >
          次へ
        </button>
      </div>
    </div>

    <!-- 編集モーダル -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <h2>{{ modalMode === 'create' ? '語彙を追加' : '語彙を編集' }}</h2>

        <form @submit.prevent="saveVocabulary">
          <div class="form-group">
            <label>単語/イディオム *</label>
            <input v-model="formData.word" type="text" required />
            <span v-if="formErrors.word" class="field-error">{{ formErrors.word[0] }}</span>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>タイプ *</label>
              <select v-model="formData.type" required>
                <option value="WORD">単語</option>
                <option value="IDIOM">イディオム</option>
              </select>
            </div>
            <div class="form-group">
              <label>難易度 *</label>
              <select v-model.number="formData.difficulty" required>
                <option :value="1">基礎</option>
                <option :value="2">上級</option>
                <option :value="3">超上級</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>意味 *</label>
            <textarea v-model="formData.meaning" rows="2" required></textarea>
            <span v-if="formErrors.meaning" class="field-error">{{ formErrors.meaning[0] }}</span>
          </div>

          <div class="form-group">
            <label>品詞</label>
            <input v-model="formData.part_of_speech" type="text" placeholder="例: 名詞、動詞" />
          </div>

          <div class="form-group">
            <label>例文</label>
            <textarea v-model="formData.example_sentence" rows="2"></textarea>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>類義語</label>
              <input v-model="formData.synonym" type="text" />
            </div>
            <div class="form-group">
              <label>対義語</label>
              <input v-model="formData.antonym" type="text" />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>頻度 (1-5)</label>
              <select v-model.number="formData.frequency">
                <option :value="1">1 (低)</option>
                <option :value="2">2</option>
                <option :value="3">3 (中)</option>
                <option :value="4">4</option>
                <option :value="5">5 (高)</option>
              </select>
            </div>
            <div class="form-group">
              <label>タグ</label>
              <input v-model="formData.tags" type="text" placeholder="カンマ区切り" />
            </div>
          </div>

          <div v-if="formErrors.general" class="form-error">{{ formErrors.general }}</div>

          <div class="modal-actions">
            <button type="button" class="cancel-btn" @click="closeModal">キャンセル</button>
            <button type="submit" class="save-btn" :disabled="isSaving">
              {{ isSaving ? '保存中...' : '保存' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.admin-vocabularies {
  max-width: 1100px;
  margin: 0 auto;
  padding: 1rem;
}

.page-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.back-btn {
  padding: 0.5rem 1rem;
  background: #f1f5f9;
  border: none;
  border-radius: 0.375rem;
  cursor: pointer;
}

.back-btn:hover {
  background: #e2e8f0;
}

.page-header h1 {
  font-size: 1.5rem;
  color: #1e293b;
  flex: 1;
}

.create-btn {
  padding: 0.5rem 1rem;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 0.375rem;
  cursor: pointer;
}

.create-btn:hover {
  background: #2563eb;
}

.filters {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
}

.filter-group {
  min-width: 150px;
}

.filter-group.search {
  flex: 1;
}

.filter-group input,
.filter-group select {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.375rem;
}

.total-count {
  color: #64748b;
  margin-bottom: 1rem;
}

.loading, .error {
  text-align: center;
  padding: 2rem;
}

.error {
  color: #ef4444;
}

.vocab-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
  border-radius: 0.5rem;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.vocab-table th,
.vocab-table td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}

.vocab-table th {
  background: #f8fafc;
  font-weight: 600;
  color: #475569;
}

.word-cell {
  font-weight: 500;
  max-width: 200px;
}

.meaning-cell {
  max-width: 250px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.type-badge {
  font-size: 0.75rem;
  padding: 0.125rem 0.5rem;
  border-radius: 0.25rem;
}

.type-badge.word {
  background: #dbeafe;
  color: #1e40af;
}

.type-badge.idiom {
  background: #fae8ff;
  color: #86198f;
}

.difficulty-badge {
  font-size: 0.75rem;
  padding: 0.125rem 0.5rem;
  border-radius: 0.25rem;
}

.diff-1 { background: #dcfce7; color: #166534; }
.diff-2 { background: #fef3c7; color: #92400e; }
.diff-3 { background: #fee2e2; color: #991b1b; }

.action-cell {
  white-space: nowrap;
}

.edit-btn,
.delete-btn {
  padding: 0.25rem 0.5rem;
  border: none;
  border-radius: 0.25rem;
  cursor: pointer;
  font-size: 0.75rem;
  margin-right: 0.25rem;
}

.edit-btn {
  background: #e2e8f0;
  color: #475569;
}

.edit-btn:hover {
  background: #cbd5e1;
}

.delete-btn {
  background: #fee2e2;
  color: #991b1b;
}

.delete-btn:hover {
  background: #fecaca;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  margin-top: 1rem;
  padding: 1rem;
}

.pagination button {
  padding: 0.5rem 1rem;
  border: 1px solid #e2e8f0;
  background: white;
  border-radius: 0.375rem;
  cursor: pointer;
}

.pagination button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination button:hover:not(:disabled) {
  background: #f8fafc;
}

.page-info {
  color: #64748b;
}

/* モーダル */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100;
}

.modal {
  background: white;
  border-radius: 0.5rem;
  padding: 1.5rem;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal h2 {
  margin-bottom: 1.5rem;
  color: #1e293b;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.25rem;
  font-weight: 500;
  color: #475569;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.375rem;
}

.form-group textarea {
  resize: vertical;
}

.form-row {
  display: flex;
  gap: 1rem;
}

.form-row .form-group {
  flex: 1;
}

.field-error {
  color: #ef4444;
  font-size: 0.75rem;
}

.form-error {
  color: #ef4444;
  margin-bottom: 1rem;
  padding: 0.5rem;
  background: #fee2e2;
  border-radius: 0.25rem;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  margin-top: 1.5rem;
}

.cancel-btn {
  padding: 0.5rem 1rem;
  background: #f1f5f9;
  border: none;
  border-radius: 0.375rem;
  cursor: pointer;
}

.cancel-btn:hover {
  background: #e2e8f0;
}

.save-btn {
  padding: 0.5rem 1rem;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 0.375rem;
  cursor: pointer;
}

.save-btn:hover {
  background: #2563eb;
}

.save-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .filters {
    flex-direction: column;
  }

  .vocab-table {
    font-size: 0.875rem;
  }

  .meaning-cell {
    max-width: 120px;
  }

  .form-row {
    flex-direction: column;
    gap: 0;
  }
}
</style>
