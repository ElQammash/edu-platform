import { api } from '@/api/client'
import type { ApiResource, Paginated } from '@/types/api'
import type { LessonComment, QaFilter } from '@/types/qa'

export type LessonCommentsPage = Paginated<LessonComment> & {
  meta: Paginated<LessonComment>['meta'] & { can_ask: boolean }
}

export async function fetchLessonComments(lessonId: number, filter: QaFilter, page: number): Promise<LessonCommentsPage> {
  const { data } = await api.get<LessonCommentsPage>(`/lessons/${lessonId}/comments`, {
    params: { filter: filter === 'all' ? undefined : filter, page },
  })
  return data
}

export async function askQuestion(lessonId: number, body: string): Promise<LessonComment> {
  const { data } = await api.post<ApiResource<LessonComment>>(`/lessons/${lessonId}/comments`, { body })
  return data.data
}

export async function replyTo(questionId: number, body: string): Promise<LessonComment> {
  const { data } = await api.post<ApiResource<LessonComment>>(`/comments/${questionId}/replies`, { body })
  return data.data
}

export async function editComment(id: number, body: string): Promise<LessonComment> {
  const { data } = await api.patch<ApiResource<LessonComment>>(`/comments/${id}`, { body })
  return data.data
}

export async function deleteComment(id: number): Promise<void> {
  await api.delete(`/comments/${id}`)
}

/** Resolve or reopen a question. */
export async function setResolved(questionId: number, resolved: boolean): Promise<LessonComment> {
  const { data } = await api.post<ApiResource<LessonComment>>(`/comments/${questionId}/${resolved ? 'resolve' : 'reopen'}`)
  return data.data
}

/** Pick a reply as the answer; returns the question. */
export async function acceptReply(replyId: number): Promise<LessonComment> {
  const { data } = await api.post<ApiResource<LessonComment>>(`/comments/${replyId}/accept`)
  return data.data
}
