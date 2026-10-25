import { api } from '@/api/client'
import { env } from '@/lib/env'
import type { ApiResource } from '@/types/api'
import type { LiveSession } from '@/types/course'

/** The meeting link, only inside the join window; records attendance. */
export async function joinLesson(lessonId: number): Promise<string> {
  const { data } = await api.post<ApiResource<{ meeting_url: string }>>(`/lessons/${lessonId}/join`)
  return data.data.meeting_url
}

export async function fetchLiveSessions(): Promise<LiveSession[]> {
  const { data } = await api.get<ApiResource<LiveSession[]>>('/me/live-sessions')
  return data.data
}

export function calendarUrl(lessonId: number): string {
  return `${env.apiUrl}/api/v1/lessons/${lessonId}/calendar.ics`
}
