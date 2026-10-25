import { api } from '@/api/client'
import type { ApiResource } from '@/types/api'
import type { Lesson, LessonProgress } from '@/types/course'

export interface ProgressResponse extends ApiResource<LessonProgress> {
  course_progress_percent: number
}

export async function fetchLesson(lessonId: number): Promise<Lesson> {
  const { data } = await api.get<ApiResource<Lesson>>(`/lessons/${lessonId}`)
  return data.data
}

export async function recordProgress(lessonId: number, watchedSeconds: number, durationSeconds?: number): Promise<ProgressResponse> {
  const { data } = await api.post<ProgressResponse>(`/lessons/${lessonId}/progress`, {
    watched_seconds: Math.floor(watchedSeconds),
    duration_seconds: durationSeconds ? Math.round(durationSeconds) : undefined,
  })
  return data
}

export async function completeLesson(lessonId: number): Promise<ProgressResponse> {
  const { data } = await api.post<ProgressResponse>(`/lessons/${lessonId}/complete`)
  return data
}
