import { api } from '@/api/client'
import type { ApiResource } from '@/types/api'
import type { AnswerInput, Attempt, Quiz } from '@/types/quiz'

export async function fetchQuiz(quizId: number): Promise<Quiz> {
  const { data } = await api.get<ApiResource<Quiz>>(`/quizzes/${quizId}`)
  return data.data
}

/** Starts a new attempt, or returns the open one. */
export async function startAttempt(quizId: number): Promise<Attempt> {
  const { data } = await api.post<ApiResource<Attempt>>(`/quizzes/${quizId}/attempts`)
  return data.data
}

export async function fetchAttempt(attemptId: number): Promise<Attempt> {
  const { data } = await api.get<ApiResource<Attempt>>(`/attempts/${attemptId}`)
  return data.data
}

export async function saveAnswers(attemptId: number, answers: AnswerInput[]): Promise<void> {
  await api.put(`/attempts/${attemptId}/answers`, { answers })
}

export async function submitAttempt(attemptId: number): Promise<Attempt> {
  const { data } = await api.post<ApiResource<Attempt>>(`/attempts/${attemptId}/submit`)
  return data.data
}
