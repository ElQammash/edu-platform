import { useTranslation } from 'react-i18next'
import { PagePlaceholder } from '@/components/PagePlaceholder'

export default function ProfilePage() {
  const { t } = useTranslation()

  return <PagePlaceholder title={t('pages.profile.title')} />
}
