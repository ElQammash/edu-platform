import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { courseKeys } from '@/features/courses/hooks'
import { fetchMyPayments, fetchPayment, startCheckout } from './api'

/** How long the result page keeps asking before saying "we'll let you know". */
export const CONFIRMATION_WAIT_MS = 2 * 60_000

export const paymentKeys = {
  detail: (reference: string) => ['payments', reference] as const,
  mine: ['me', 'payments'] as const,
}

/** Starts checkout, then leaves the app for the payment page. */
export function useCheckout(slug: string) {
  return useMutation({
    mutationFn: () => startCheckout(slug),
    onSuccess: (payment) => window.location.assign(payment.checkout_url),
  })
}

/**
 * The payment, checked every 3 seconds while the gateway hasn't confirmed
 * it (for up to CONFIRMATION_WAIT_MS). Once it is paid, the course and
 * My courses are refreshed so the new enrollment shows.
 */
export function usePayment(reference: string, startedAt: number) {
  const queryClient = useQueryClient()

  return useQuery({
    queryKey: paymentKeys.detail(reference),
    queryFn: async () => {
      const payment = await fetchPayment(reference)
      if (payment.status === 'paid') {
        void queryClient.invalidateQueries({ queryKey: courseKeys.detail(payment.course.slug) })
        void queryClient.invalidateQueries({ queryKey: courseKeys.mine })
        void queryClient.invalidateQueries({ queryKey: paymentKeys.mine })
      }
      return payment
    },
    refetchInterval: (query) =>
      query.state.data?.status === 'pending' && Date.now() - startedAt < CONFIRMATION_WAIT_MS ? 3000 : false,
  })
}

export function useMyPayments() {
  return useQuery({
    queryKey: paymentKeys.mine,
    queryFn: fetchMyPayments,
  })
}
