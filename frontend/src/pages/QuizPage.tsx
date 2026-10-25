import axios from 'axios'
import { ArrowLeft, CalendarClock, ListChecks, Repeat, Target, Timer } from 'lucide-react'
import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useNavigate, useParams } from 'react-router'
import { ErrorState } from '@/components/ErrorState'
import { FullPageSpinner } from '@/components/FullPageSpinner'
import { Markdown } from '@/components/Markdown'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { useQuiz, useStartAttempt } from '@/features/quizzes/hooks'
import { useFormatters } from '@/hooks/useFormatters'
import { getGeneralError } from '@/lib/apiErrors'
import { paths } from '@/routes/paths'
import type { AttemptSummary, Quiz } from '@/types/quiz'

export default function QuizPage() {
  const { t } = useTranslation()
  const { quizId } = useParams()
  const { data: quiz, isPending, isError, error, refetch } = useQuiz(Number(quizId))

  if (isPending) return <FullPageSpinner />

  if (isError) {
    const status = axios.isAxiosError(error) ? error.response?.status : undefined
    if (status === 403) return <ErrorState message={t('quizzes.locked')} />
    if (status === 404) return <ErrorState message={t('quizzes.notFound')} />
    return <ErrorState onRetry={() => void refetch()} />
  }

  return <QuizIntro quiz={quiz} />
}

function QuizIntro({ quiz }: { quiz: Quiz }) {
  const { t } = useTranslation()
  const format = useFormatters()
  const navigate = useNavigate()
  const start = useStartAttempt(quiz.id)
  const attempts = quiz.attempts ?? []
  const startError = getGeneralError(start.error, t('auth.errors.generic'))

  return (
    <div className="mx-auto max-w-3xl space-y-6 px-4 py-8">
      <Link
        to={paths.lesson(quiz.lesson.id)}
        className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
      >
        <ArrowLeft className="size-4 rtl:rotate-180" />
        {t('quizzes.backToLesson')}
      </Link>

      <header className="space-y-2">
        <p dir="auto" className="text-sm text-muted-foreground">
          {quiz.course.title} · {quiz.lesson.title}
        </p>
        <h1 dir="auto" className="text-3xl font-bold">
          {quiz.title}
        </h1>
      </header>

      <ul className="grid gap-3 text-sm sm:grid-cols-2">
        <Fact icon={ListChecks}>
          {t('quizzes.questions', { count: quiz.questions_count, value: format.number(quiz.questions_count) })} · {t('quizzes.points', { count: quiz.total_points, value: format.number(quiz.total_points) })}
        </Fact>
        <Fact icon={Timer}>
          {quiz.time_limit_minutes ? t('quizzes.timeLimit', { value: format.number(quiz.time_limit_minutes) }) : t('quizzes.untimed')}
        </Fact>
        <Fact icon={Target}>{t('quizzes.passScore', { value: format.number(quiz.pass_score) })}</Fact>
        <Fact icon={Repeat}>
          {t('quizzes.attemptsAllowed', { count: quiz.max_attempts, value: format.number(quiz.max_attempts) })}
          {quiz.max_attempts > 1 && ` · ${t('quizzes.bestCounts')}`}
        </Fact>
        {quiz.due_at && <Fact icon={CalendarClock}>{t('quizzes.due', { date: format.dateTime(quiz.due_at) })}</Fact>}
      </ul>

      {quiz.description && <Markdown>{quiz.description}</Markdown>}

      {startError && (
        <Alert variant="destructive">
          <AlertDescription>{startError}</AlertDescription>
        </Alert>
      )}

      {quiz.can_start ? (
        <Button
          size="lg"
          disabled={start.isPending}
          onClick={() => start.mutate(undefined, { onSuccess: (attempt) => navigate(paths.attempt(attempt.id)) })}
        >
          {start.isPending ? t('quizzes.starting') : quiz.open_attempt_id ? t('quizzes.resume') : t('quizzes.start')}
        </Button>
      ) : (
        quiz.cannot_start_reason && (
          <Alert>
            <AlertDescription>{quiz.cannot_start_reason}</AlertDescription>
          </Alert>
        )
      )}

      {attempts.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>{t('quizzes.yourAttempts')}</CardTitle>
          </CardHeader>
          <CardContent className="divide-y p-0">
            {attempts.map((attempt, i) => (
              <AttemptRow key={attempt.id} attempt={attempt} number={attempts.length - i} />
            ))}
          </CardContent>
        </Card>
      )}
    </div>
  )
}

function Fact({ icon: Icon, children }: { icon: typeof Timer; children: ReactNode }) {
  return (
    <li className="flex items-center gap-2 rounded-md border px-3 py-2">
      <Icon className="size-4 shrink-0 text-muted-foreground" />
      <span>{children}</span>
    </li>
  )
}

function AttemptRow({ attempt, number }: { attempt: AttemptSummary; number: number }) {
  const { t } = useTranslation()
  const format = useFormatters()

  return (
    <div className="flex flex-wrap items-center gap-3 px-6 py-3 text-sm">
      <span className="font-medium">{t('quizzes.attempt', { number: format.number(number) })}</span>
      <Badge variant={attempt.status === 'graded' ? 'default' : 'secondary'}>{t(`quizzes.status.${attempt.status}`)}</Badge>
      {attempt.status === 'graded' && (
        <>
          <span dir="ltr">
            {t('quizzes.result.score', { score: format.number(attempt.score ?? 0), max: format.number(attempt.max_score) })}
          </span>
          <Badge variant={attempt.passed ? 'default' : 'destructive'}>{attempt.passed ? t('quizzes.passed') : t('quizzes.failed')}</Badge>
        </>
      )}
      <Button asChild variant="link" size="sm" className="ms-auto">
        <Link to={paths.attempt(attempt.id)}>{attempt.status === 'in_progress' ? t('quizzes.resume') : t('quizzes.viewResult')}</Link>
      </Button>
    </div>
  )
}
