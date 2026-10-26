import { api } from '@/api/client'
import type { ApiResource, Paginated } from '@/types/api'
import type { Payment } from '@/types/payment'

/** Starts (or reuses) a checkout; the response always carries checkout_url. */
export async function startCheckout(slug: string): Promise<Payment & { checkout_url: string }> {
  const { data } = await api.post<ApiResource<Payment & { checkout_url: string }>>(`/courses/${encodeURIComponent(slug)}/checkout`)
  return data.data
}

export async function fetchPayment(reference: string): Promise<Payment> {
  const { data } = await api.get<ApiResource<Payment>>(`/payments/${encodeURIComponent(reference)}`)
  return data.data
}

export async function fetchMyPayments(): Promise<Paginated<Payment>> {
  const { data } = await api.get<Paginated<Payment>>('/me/payments')
  return data
}
