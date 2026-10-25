import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { Button } from '@/components/ui/button'
import { paths } from '@/routes/paths'

export default function NotFoundPage() {
  const { t } = useTranslation()

  return (
    <section className="mx-auto max-w-3xl px-4 py-24 text-center">
      <h1 className="text-3xl font-bold">{t('pages.notFound.title')}</h1>
      <p className="mt-2 text-muted-foreground">{t('pages.notFound.description')}</p>
      <Button asChild className="mt-6">
        <Link to={paths.home}>{t('pages.notFound.backHome')}</Link>
      </Button>
    </section>
  )
}
