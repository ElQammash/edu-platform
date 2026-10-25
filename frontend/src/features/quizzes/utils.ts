import type { TFunction } from 'i18next'
import type { AttemptQuestion } from '@/types/quiz'

/** True/false options are stored as "True"/"False" and shown in the UI language. */
export function optionLabel(question: AttemptQuestion, body: string, t: TFunction): string {
  if (question.type !== 'true_false') return body
  return body === 'True' ? t('quizzes.true') : t('quizzes.false')
}
