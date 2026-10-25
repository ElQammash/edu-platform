import { useTranslation } from 'react-i18next'
import type { Language } from '@/lib/i18n'

export function useLanguage() {
  const { i18n } = useTranslation()
  const language = (i18n.resolvedLanguage ?? 'ar') as Language

  return {
    language,
    dir: i18n.dir(language),
    setLanguage: (next: Language) => i18n.changeLanguage(next),
  }
}
