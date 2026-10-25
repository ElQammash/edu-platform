import { useTranslation } from 'react-i18next'
import { PagePlaceholder } from '@/components/PagePlaceholder'

export default function CatalogPage() {
  const { t } = useTranslation()

  return <PagePlaceholder title={t('pages.catalog.title')} />
}
