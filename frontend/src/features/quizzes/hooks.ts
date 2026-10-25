import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { fetchAttempt, fetchQuiz, saveAnswers, startAttempt, submitAttempt } from './api'
import type { AnswerInput } from '@/types/quiz'

export const quizKeys = {
  quiz: (quizId: number) => ['quizzes', quizId] as const,
  attempt: (attemptId: number) => ['attempts', attemptId] as const,
}

export function useQuiz(quizId: number) {
  return useQuery({
    queryKey: quizKeys.quiz(quizId),
    queryFn: () => fetchQuiz(quizId),
    enabled: Number.isInteger(quizId) && quizId > 0,
  })
}

export function useAttempt(attemptId: number) {
  return useQuery({
    queryKey: quizKeys.attempt(attemptId),
    queryFn: () => fetchAttempt(attemptId),
    enabled: Number.isInteger(attemptId) && attemptId > 0,
    // Answers live in the page while taking the quiz; do not overwrite them.
    refetchOnWindowFocus: false,
  })
}

export function useStartAttempt(quizId: number) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: () => startAttempt(quizId),
    onSuccess: (attempt) => queryClient.setQueryData(quizKeys.attempt(attempt.id), attempt),
  })
}

export function useSaveAnswers(attemptId: number) {
  return useMutation({
    mutationFn: (answers: AnswerInput[]) => saveAnswers(attemptId, answers),
    retry: 2,
  })
}

export function useSubmitAttempt(attemptId: number, quizId: number | undefined) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: () => submitAttempt(attemptId),
    onSuccess: (attempt) => {
      queryClient.setQueryData(quizKeys.attempt(attemptId), attempt)
      if (quizId) void queryClient.invalidateQueries({ queryKey: quizKeys.quiz(quizId) })
      void queryClient.invalidateQueries({ queryKey: ['me', 'grades'] })
    },
  })
}
