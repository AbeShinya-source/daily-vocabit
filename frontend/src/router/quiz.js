// src/stores/quiz.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

/**
 * Question 型イメージ
 * {
 *   id: number,
 *   type: 'vocab' | 'idiom',
 *   difficulty: 1 | 2 | 3,
 *   questionText: string,
 *   choices: string[],
 *   correctIndex: number,  // 0〜3
 *   explanation: string
 * }
 */

export const useQuizStore = defineStore('quiz', () => {
  const mode = ref(null) // 'vocab' | 'idiom'
  const difficulty = ref(null) // 1 | 2 | 3
  const questions = ref([]) // Question[]
  const currentIndex = ref(0)
  const answers = ref([]) // { questionId, selectedIndex, isCorrect }[]
  const isLoaded = ref(false)

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
  }

  function setModeAndDifficulty(newMode, newDifficulty) {
    mode.value = newMode
    difficulty.value = newDifficulty
  }

  /**
   * 本来はここで API から「今日の問題」を取得する想定。
   * いまはモックで10問生成。
   */
  async function loadDailyQuestions() {
    if (!mode.value || !difficulty.value) {
      throw new Error('mode and difficulty must be set before loading questions')
    }

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

  /**
   * 現在の問題に対する回答を登録し、結果を返す
   */
  function answerCurrentQuestion(selectedIndex) {
    const q = currentQuestion.value
    if (!q) return null

    const isCorrect = selectedIndex === q.correctIndex

    answers.value.push({
      questionId: q.id,
      selectedIndex,
      isCorrect,
    })

    return { isCorrect, correctIndex: q.correctIndex }
  }

  function goToNextQuestion() {
    if (currentIndex.value < totalQuestions.value) {
      currentIndex.value += 1
    }
  }

  return {
    // state
    mode,
    difficulty,
    questions,
    currentIndex,
    answers,
    isLoaded,
    // getters
    currentQuestion,
    totalQuestions,
    isFinished,
    score,
    // actions
    resetQuiz,
    setModeAndDifficulty,
    loadDailyQuestions,
    answerCurrentQuestion,
    goToNextQuestion,
  }
})
