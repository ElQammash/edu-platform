import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import type { QaFilter } from '@/types/qa'
import { acceptReply, askQuestion, deleteComment, editComment, fetchLessonComments, replyTo, setResolved } from './api'

export const qaKeys = {
  lesson: (lessonId: number) => ['lessons', lessonId, 'comments'] as const,
  page: (lessonId: number, filter: QaFilter, page: number) => ['lessons', lessonId, 'comments', filter, page] as const,
}

export function useLessonComments(lessonId: number, filter: QaFilter, page: number) {
  return useQuery({
    queryKey: qaKeys.page(lessonId, filter, page),
    queryFn: () => fetchLessonComments(lessonId, filter, page),
    placeholderData: keepPreviousData,
  })
}

/**
 * Every Q&A change refetches the lesson's threads: replies, answers and
 * resolved states move together, and the server decides what each viewer
 * may do next.
 */
function useQaMutation<TVariables>(lessonId: number, mutationFn: (variables: TVariables) => Promise<unknown>) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: qaKeys.lesson(lessonId) }),
  })
}

export function useAskQuestion(lessonId: number) {
  return useQaMutation(lessonId, (body: string) => askQuestion(lessonId, body))
}

export function useReply(lessonId: number, questionId: number) {
  return useQaMutation(lessonId, (body: string) => replyTo(questionId, body))
}

export function useEditComment(lessonId: number, commentId: number) {
  return useQaMutation(lessonId, (body: string) => editComment(commentId, body))
}

export function useDeleteComment(lessonId: number) {
  return useQaMutation(lessonId, (commentId: number) => deleteComment(commentId))
}

export function useSetResolved(lessonId: number, questionId: number) {
  return useQaMutation(lessonId, (resolved: boolean) => setResolved(questionId, resolved))
}

export function useAcceptReply(lessonId: number) {
  return useQaMutation(lessonId, (replyId: number) => acceptReply(replyId))
}
