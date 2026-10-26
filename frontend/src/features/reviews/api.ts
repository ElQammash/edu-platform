import { api } from '@/api/client'
import type { ApiResource, Paginated } from '@/types/api'
import type { MyReview, Review, ReviewSummary } from '@/types/review'

export type ReviewsPage = Paginated<Review> & { meta: Paginated<Review>['meta'] & { summary: ReviewSummary } }

const base = (slug: string) => `/courses/${encodeURIComponent(slug)}`

export async function fetchReviews(slug: string, page: number): Promise<ReviewsPage> {
  const { data } = await api.get<ReviewsPage>(`${base(slug)}/reviews`, { params: { page } })
  return data
}

export async function fetchMyReview(slug: string): Promise<MyReview> {
  const { data } = await api.get<MyReview>(`${base(slug)}/review`)
  return data
}

export async function saveReview(slug: string, input: { rating: number; comment: string }): Promise<Review> {
  const { data } = await api.put<ApiResource<Review>>(`${base(slug)}/review`, input)
  return data.data
}

export async function deleteReview(slug: string): Promise<void> {
  await api.delete(`${base(slug)}/review`)
}
