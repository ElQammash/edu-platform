export type PaymentState = 'pending' | 'paid' | 'failed' | 'expired' | 'partially_refunded' | 'refunded'

export interface Payment {
  reference: string
  status: PaymentState
  amount: number
  refunded_amount: number
  currency: string
  /** e.g. "card •••• 2346" or "wallet". */
  payment_method: string | null
  course: { id: number; slug: string; title: string }
  /** Only while the checkout can still be finished. */
  checkout_url: string | null
  created_at: string
  paid_at: string | null
  refunded_at: string | null
}

export interface PaymentNotificationData {
  reference: string
  amount: number
  currency: string
  course_id: number
  course_slug: string
  course_title: string
  /** Refund notices. */
  refunded?: number
  full?: boolean
}
