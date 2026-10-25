import { useEffect } from 'react'
import { useQueryClient } from '@tanstack/react-query'
import { useLanguage } from '@/hooks/useLanguage'
import type { User } from '@/types/user'
import { saveLocale } from './api'
import { authKeys, useAuth } from './hooks'

/**
 * Keep the signed-in user's saved language in step with the one chosen in
 * the app, so reminder emails arrive in the language they read.
 */
export function useSyncAccountLanguage() {
  const { user } = useAuth()
  const { language } = useLanguage()
  const queryClient = useQueryClient()

  useEffect(() => {
    if (!user || user.locale === language) return

    saveLocale(language)
      .then(() => queryClient.setQueryData<User | null>(authKeys.me, (current) => current && { ...current, locale: language }))
      // Not critical: it is retried on the next change or page load.
      .catch(() => undefined)
  }, [user, language, queryClient])
}
