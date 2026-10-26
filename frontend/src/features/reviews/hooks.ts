import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { courseKeys } from '@/features/courses/hooks'
import { deleteReview, fetchMyReview, fetchReviews, saveReview } from './api'

export const reviewKeys = {
  all: (slug: string) => ['courses', 'reviews', slug] as const,
  list: (slug: string, page: number) => ['courses', 'reviews', slug, 'list', page] as const,
  mine: (slug: string) => ['courses', 'reviews', slug, 'mine'] as const,
}

export function useReviews(slug: string, page: number) {
  return useQuery({
    queryKey: reviewKeys.list(slug, page),
    queryFn: () => fetchReviews(slug, page),
    placeholderData: keepPreviousData,
  })
}

export function useMyReview(slug: string, enabled: boolean) {
  return useQuery({
    queryKey: reviewKeys.mine(slug),
    queryFn: () => fetchMyReview(slug),
    enabled,
  })
}

/** A review changes the course's rating too (course page, catalog cards). */
function useInvalidateReviews(slug: string) {
  const queryClient = useQueryClient()

  return () => {
    void queryClient.invalidateQueries({ queryKey: reviewKeys.all(slug) })
    void queryClient.invalidateQueries({ queryKey: courseKeys.detail(slug) })
    void queryClient.invalidateQueries({ queryKey: ['courses', 'list'] })
  }
}

export function useSaveReview(slug: string) {
  const invalidate = useInvalidateReviews(slug)

  return useMutation({
    mutationFn: (input: { rating: number; comment: string }) => saveReview(slug, input),
    onSuccess: invalidate,
  })
}

export function useDeleteReview(slug: string) {
  const invalidate = useInvalidateReviews(slug)

  return useMutation({
    mutationFn: () => deleteReview(slug),
    onSuccess: invalidate,
  })
}
