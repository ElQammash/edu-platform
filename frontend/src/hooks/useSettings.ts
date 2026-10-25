import { useQuery } from '@tanstack/react-query'
import { fetchSettings } from '@/api/settings'

export function useSettings() {
  return useQuery({
    queryKey: ['settings'],
    queryFn: fetchSettings,
    staleTime: 30 * 60_000,
  })
}

/** wa.me link with a pre-filled message, or null when no number is configured. */
export function whatsAppLink(number: string | null | undefined, message: string): string | null {
  return number ? `https://wa.me/${number}?text=${encodeURIComponent(message)}` : null
}
