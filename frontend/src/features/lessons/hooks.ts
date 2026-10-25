import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { courseKeys } from '@/features/courses/hooks'
import type { Lesson } from '@/types/course'
import { completeLesson, fetchLesson, recordProgress, type ProgressResponse } from './api'

export const lessonKeys = {
  detail: (lessonId: number) => ['lessons', lessonId] as const,
}

export function useLesson(lessonId: number) {
  return useQuery({
    queryKey: lessonKeys.detail(lessonId),
    queryFn: () => fetchLesson(lessonId),
    enabled: Number.isInteger(lessonId) && lessonId > 0,
  })
}

/**
 * Keep the lesson cache in sync with a progress answer, and refresh the
 * course page / my courses when the lesson just became complete.
 */
function useApplyProgress(lessonId: number, courseSlug: string | undefined) {
  const queryClient = useQueryClient()

  return (response: ProgressResponse) => {
    const previous = queryClient.getQueryData<Lesson>(lessonKeys.detail(lessonId))
    queryClient.setQueryData<Lesson>(lessonKeys.detail(lessonId), (lesson) => lesson && { ...lesson, progress: response.data })

    if (response.data.is_completed && !previous?.progress?.is_completed) {
      if (courseSlug) void queryClient.invalidateQueries({ queryKey: courseKeys.detail(courseSlug) })
      void queryClient.invalidateQueries({ queryKey: courseKeys.mine })
    }
  }
}

export function useRecordProgress(lessonId: number, courseSlug: string | undefined) {
  const apply = useApplyProgress(lessonId, courseSlug)

  return useMutation({
    mutationFn: ({ watched, duration }: { watched: number; duration?: number }) => recordProgress(lessonId, watched, duration),
    onSuccess: apply,
  })
}

export function useCompleteLesson(lessonId: number, courseSlug: string | undefined) {
  const apply = useApplyProgress(lessonId, courseSlug)

  return useMutation({
    mutationFn: () => completeLesson(lessonId),
    onSuccess: apply,
  })
}
