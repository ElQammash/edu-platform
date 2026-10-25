import { useTranslation } from 'react-i18next'
import { PagePlaceholder } from '@/components/PagePlaceholder'

export default function LoginPage() {
  const { t } = useTranslation()

  return <PagePlaceholder title={t('pages.login.title')} />
}
