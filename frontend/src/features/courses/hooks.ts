import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import type { CourseFilters } from '@/types/course'
import { enroll, fetchCourse, fetchCourses, fetchMyCourses } from './api'

export const courseKeys = {
  all: ['courses'] as const,
  list: (filters: CourseFilters) => ['courses', 'list', filters] as const,
  detail: (slug: string) => ['courses', 'detail', slug] as const,
  mine: ['me', 'courses'] as const,
}

export function useCourses(filters: CourseFilters) {
  return useQuery({
    queryKey: courseKeys.list(filters),
    queryFn: () => fetchCourses(filters),
    // Keep showing the previous page while the next one loads.
    placeholderData: keepPreviousData,
  })
}

export function useCourse(slug: string) {
  return useQuery({
    queryKey: courseKeys.detail(slug),
    queryFn: () => fetchCourse(slug),
  })
}

export function useMyCourses() {
  return useQuery({
    queryKey: courseKeys.mine,
    queryFn: fetchMyCourses,
  })
}

export function useEnroll(slug: string) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: () => enroll(slug),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: courseKeys.detail(slug) })
      void queryClient.invalidateQueries({ queryKey: courseKeys.mine })
    },
  })
}
