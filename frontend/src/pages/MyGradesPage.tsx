import { useTranslation } from 'react-i18next'
import { PagePlaceholder } from '@/components/PagePlaceholder'

export default function MyGradesPage() {
  const { t } = useTranslation()

  return <PagePlaceholder title={t('pages.myGrades.title')} />
}
