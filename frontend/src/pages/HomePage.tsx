import { useTranslation } from 'react-i18next'

export default function HomePage() {
  const { t } = useTranslation()

  return (
    <section className="mx-auto max-w-3xl px-4 py-24 text-center">
      <h1 className="text-4xl font-bold">{t('app.name')}</h1>
      <p className="mt-4 text-lg text-muted-foreground">{t('app.tagline')}</p>
    </section>
  )
}
