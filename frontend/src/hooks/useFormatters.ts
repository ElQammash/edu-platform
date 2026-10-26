import { useMemo } from 'react'
import { useTranslation } from 'react-i18next'
import { useLanguage } from '@/hooks/useLanguage'

/**
 * Number, price and duration formatting in the active language. Arabic
 * uses Egyptian conventions (ar-EG), including Arabic-Indic digits.
 */
export function useFormatters() {
  const { t } = useTranslation()
  const { language } = useLanguage()
  const locale = language === 'ar' ? 'ar-EG' : 'en-EG'

  return useMemo(() => {
    const currency = new Intl.NumberFormat(locale, { style: 'currency', currency: 'EGP', maximumFractionDigits: 0 })
    const number = new Intl.NumberFormat(locale)
    const date = new Intl.DateTimeFormat(locale, { dateStyle: 'medium' })
    const dateTime = new Intl.DateTimeFormat(locale, { dateStyle: 'medium', timeStyle: 'short' })
    const relativeTime = new Intl.RelativeTimeFormat(locale, { numeric: 'auto' })

    return {
      price: (amount: number) => (amount === 0 ? t('courses.free') : currency.format(amount)),
      number: (value: number) => number.format(value),
      date: (iso: string) => date.format(new Date(iso)),
      dateTime: (iso: string) => dateTime.format(new Date(iso)),
      /** "5 minutes ago", "yesterday"; older than a week falls back to the date. */
      relative: (iso: string) => {
        const seconds = Math.round((new Date(iso).getTime() - Date.now()) / 1000)
        const abs = Math.abs(seconds)
        if (abs < 60) return relativeTime.format(0, 'second')
        if (abs < 3600) return relativeTime.format(Math.round(seconds / 60), 'minute')
        if (abs < 86_400) return relativeTime.format(Math.round(seconds / 3600), 'hour')
        if (abs < 7 * 86_400) return relativeTime.format(Math.round(seconds / 86_400), 'day')
        return date.format(new Date(iso))
      },
      /** "1 h 20 min" style; null/0 gives an empty string. */
      duration: (seconds: number | null | undefined) => {
        if (!seconds) return ''
        const totalMinutes = Math.max(1, Math.round(seconds / 60))
        const hours = Math.floor(totalMinutes / 60)
        const minutes = totalMinutes % 60
        const parts = []
        if (hours) parts.push(t('format.hours', { value: number.format(hours) }))
        if (minutes) parts.push(t('format.minutes', { value: number.format(minutes) }))
        return parts.join(' ')
      },
    }
  }, [locale, t])
}
