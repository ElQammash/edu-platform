import { useTranslation } from 'react-i18next'
import { PagePlaceholder } from '@/components/PagePlaceholder'

export default function QuizPage() {
  const { t } = useTranslation()

  return <PagePlaceholder title={t('pages.quiz.title')} />
}
