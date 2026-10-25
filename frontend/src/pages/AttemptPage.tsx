import axios from 'axios'
import { ArrowLeft } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { Link, useParams } from 'react-router'
import { ErrorState } from '@/components/ErrorState'
import { FullPageSpinner } from '@/components/FullPageSpinner'
import { AttemptResult } from '@/features/quizzes/components/AttemptResult'
import { QuizTaker } from '@/features/quizzes/components/QuizTaker'
import { useAttempt } from '@/features/quizzes/hooks'
import { paths } from '@/routes/paths'

/** /attempts/:id — answer an open attempt, or see its result once submitted. */
export default function AttemptPage() {
  const { t } = useTranslation()
  const { attemptId } = useParams()
  const { data: attempt, isPending, isError, error, refetch } = useAttempt(Number(attemptId))

  if (isPending) return <FullPageSpinner />

  if (isError) {
    const status = axios.isAxiosError(error) ? error.response?.status : undefined
    if (status === 403 || status === 404) return <ErrorState message={t('quizzes.notFound')} />
    return <ErrorState onRetry={() => void refetch()} />
  }

  return (
    <div className="mx-auto max-w-3xl space-y-4 px-4 py-6">
      <Link
        to={paths.quiz(attempt.quiz.id)}
        className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
      >
        <ArrowLeft className="size-4 rtl:rotate-180" />
        {attempt.quiz.title}
      </Link>

      {attempt.status === 'in_progress' ? (
        // Remount when the attempt is reloaded after submitting.
        <QuizTaker key={attempt.server_time} attempt={attempt} />
      ) : (
        <AttemptResult attempt={attempt} />
      )}
    </div>
  )
}
