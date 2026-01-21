// src/stores/quiz.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { questionsApi, answersApi } from '@/api/client'

export const useQuizStore = defineStore('quiz', () => {
  const mode = ref(null) // 'vocab' | 'idiom'
  const difficulty = ref(null) // 1 | 2 | 3
  const questions = ref([]) // Question[]
  const currentIndex = ref(0)
  const answers = ref([]) // { questionId, selectedIndex, isCorrect }[]
  const isLoaded = ref(false)
  const sessionId = ref(null) // Quiz session ID for tracking

  const currentQuestion = computed(() => {
    return questions.value[currentIndex.value] || null
  })

  const totalQuestions = computed(() => questions.value.length)
  const isFinished = computed(() => currentIndex.value >= totalQuestions.value)

  const score = computed(() => {
    return answers.value.filter((a) => a.isCorrect).length
  })

  function resetQuiz() {
    mode.value = null
    difficulty.value = null
    questions.value = []
    currentIndex.value = 0
    answers.value = []
    isLoaded.value = false
    sessionId.value = null
  }

  function setModeAndDifficulty(newMode, newDifficulty) {
    mode.value = newMode
    difficulty.value = newDifficulty
  }

  async function loadDailyQuestions() {
    if (!difficulty.value) {
      throw new Error('difficulty must be set before loading questions')
    }

    try {
      // APIから今日の問題を取得（単語とイディオムの組み合わせ）
      const response = await questionsApi.getDaily({
        difficulty: difficulty.value,
      })

      if (response.success && response.data.questions) {
        // APIレスポンスをフロントエンド形式に変換
        questions.value = response.data.questions.map((q) => ({
          id: q.id,
          type: q.vocabulary?.type === 'IDIOM' ? 'idiom' : 'vocab', // 語彙のtypeから判定
          difficulty: q.difficulty,
          questionText: q.questionText,
          questionTranslation: q.questionTranslation,
          choices: Array.isArray(q.choices) ? q.choices : JSON.parse(q.choices || '[]'),
          correctIndex: Number(q.correctIndex),
          explanation: q.explanation,
          vocabulary: q.vocabulary,
        }))

        currentIndex.value = 0
        answers.value = []
        isLoaded.value = true
      } else {
        throw new Error('Failed to load questions from API')
      }
    } catch (error) {
      console.error('Error loading daily questions:', error)
      // フォールバック: モックデータを使用
      loadMockQuestions()
    }
  }

  function loadMockQuestions() {
    const baseId = mode.value === 'vocab' ? 1000 : 2000

    const mock = Array.from({ length: 10 }).map((_, idx) => ({
      id: baseId + idx,
      type: mode.value,
      difficulty: difficulty.value,
      questionText:
        mode.value === 'vocab'
          ? `Q${idx + 1}. Choose the best word to complete the sentence.`
          : `Q${idx + 1}. Choose the best idiom for the sentence.`,
      choices:
        mode.value === 'vocab'
          ? ['expand', 'expect', 'export', 'expose']
          : ['hit the books', 'over the moon', 'in hot water', 'under the weather'],
      correctIndex: 0,
      explanation:
        mode.value === 'vocab'
          ? '「expand」は「拡大する」という意味で、この文脈に最も適しています。'
          : '「hit the books」は「勉強を始める」という意味のイディオムです。',
    }))

    questions.value = mock
    currentIndex.value = 0
    answers.value = []
    isLoaded.value = true
  }

  async function answerCurrentQuestion(selectedIndex) {
    const q = currentQuestion.value
    if (!q) return null

    const isCorrect = selectedIndex === q.correctIndex

    // ローカルに保存
    answers.value.push({
      questionId: q.id,
      selectedIndex,
      isCorrect,
    })

    // APIに回答を記録（バックグラウンドで実行）
    try {
      await answersApi.submit({
        question_id: q.id,
        selected_index: selectedIndex,
      })
    } catch (error) {
      console.error('Failed to submit answer to API:', error)
      // エラーでも続行（ローカルには保存済み）
    }

    return { isCorrect, correctIndex: q.correctIndex }
  }

  function goToNextQuestion() {
    if (currentIndex.value < totalQuestions.value) {
      currentIndex.value += 1
    }
  }

  return {
    mode,
    difficulty,
    questions,
    currentIndex,
    answers,
    isLoaded,
    sessionId,
    currentQuestion,
    totalQuestions,
    isFinished,
    score,
    resetQuiz,
    setModeAndDifficulty,
    loadDailyQuestions,
    answerCurrentQuestion,
    goToNextQuestion,
  }
})
