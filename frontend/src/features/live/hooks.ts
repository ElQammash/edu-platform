import { useMutation, useQuery } from '@tanstack/react-query'
import { fetchLiveSessions, joinLesson } from './api'

export function useLiveSessions(enabled = true) {
  return useQuery({
    queryKey: ['me', 'live-sessions'],
    queryFn: fetchLiveSessions,
    enabled,
    // States change with the clock (upcoming -> live -> ended).
    refetchInterval: 60_000,
  })
}

export function useJoinLesson(lessonId: number) {
  return useMutation({
    mutationFn: () => joinLesson(lessonId),
  })
}
