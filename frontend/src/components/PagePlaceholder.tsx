import { useTranslation } from 'react-i18next'

/** Temporary page body until the feature lands in its phase. */
export function PagePlaceholder({ title }: { title: string }) {
  const { t } = useTranslation()

  return (
    <section className="mx-auto max-w-3xl px-4 py-16 text-center">
      <h1 className="text-2xl font-bold">{title}</h1>
      <p className="mt-2 text-muted-foreground">{t('pages.comingSoon')}</p>
    </section>
  )
}
