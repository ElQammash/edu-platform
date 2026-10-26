export interface Review {
  id: number
  rating: number
  comment: string | null
  /** First name only. */
  student: { name: string; avatar_url: string | null }
  teacher_reply: string | null
  teacher_replied_at: string | null
  created_at: string
  updated_at: string
  is_mine: boolean
  /** Only ever true on the author's own review. */
  is_hidden: boolean
}

export interface RatingSummary {
  average: number
  count: number
}

export interface ReviewSummary extends RatingSummary {
  /** Stars "5" down to "1" → number of reviews. */
  breakdown: Record<string, number>
}

export interface MyReview {
  data: Review | null
  meta: { can_review: boolean; reason: string | null; min_progress: number }
}
