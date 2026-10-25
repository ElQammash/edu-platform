import { CircleCheck, CircleX, Clock, Trophy } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { optionLabel } from '@/features/quizzes/utils'
import { useFormatters } from '@/hooks/useFormatters'
import { cn } from '@/lib/utils'
import type { Attempt, AttemptQuestion } from '@/types/quiz'

/**
 * A submitted attempt. The score shows once graded; which answers were
 * right shows only when the API reveals it (per the quiz's setting).
 */
export function AttemptResult({ attempt }: { attempt: Attempt }) {
  const { t } = useTranslation()
  const format = useFormatters()
  const graded = attempt.status === 'graded'

  return (
    <div className="space-y-6">
      <h1 dir="auto" className="text-2xl font-bold">
        {attempt.quiz.title}
      </h1>

      {graded ? (
        <div
          className={cn(
            'flex flex-wrap items-center gap-4 rounded-lg p-5',
            attempt.passed ? 'bg-green-50 text-green-900 dark:bg-green-950 dark:text-green-100' : 'bg-red-50 text-red-900 dark:bg-red-950 dark:text-red-100',
          )}
        >
          <Trophy className="size-8" />
          <div className="me-auto">
            <p className="text-sm">{t('quizzes.result.title')}</p>
            <p className="text-3xl font-bold" dir="ltr">
              {t('quizzes.result.score', { score: format.number(attempt.score ?? 0), max: format.number(attempt.max_score) })}
            </p>
          </div>
          <div className="text-end">
            <p className="text-2xl font-semibold" dir="ltr">
              {format.number(attempt.percent ?? 0)}%
            </p>
            <Badge variant={attempt.passed ? 'default' : 'destructive'}>{attempt.passed ? t('quizzes.passed') : t('quizzes.failed')}</Badge>
          </div>
        </div>
      ) : (
        <Alert>
          <Clock />
          <AlertDescription>{t('quizzes.result.pending')}</AlertDescription>
        </Alert>
      )}

      {graded && !attempt.answers_revealed && (
        <p className="text-sm text-muted-foreground">
          {attempt.quiz.show_answers === 'never' ? t('quizzes.result.hiddenNever') : t('quizzes.result.hiddenUntilDue')}
        </p>
      )}

      {attempt.questions.map((question, index) => (
        <ResultQuestion key={question.id} number={index + 1} question={question} revealed={attempt.answers_revealed} />
      ))}
    </div>
  )
}

function ResultQuestion({ number, question, revealed }: { number: number; question: AttemptQuestion; revealed: boolean }) {
  const { t } = useTranslation()
  const format = useFormatters()
  const chosen = question.answer.option_id
  const isCorrect = revealed && question.points_awarded !== undefined && question.points_awarded >= question.points

  return (
    <Card>
      <CardHeader className="flex-row items-start justify-between gap-4">
        <CardTitle className="flex gap-2 text-base leading-7 font-medium">
          <span className="text-muted-foreground">{format.number(number)}.</span>
          <span dir="auto" className="whitespace-pre-line">
            {question.body}
          </span>
        </CardTitle>
        {revealed && question.points_awarded !== undefined ? (
          <Badge variant={isCorrect ? 'default' : 'secondary'} dir="ltr">
            {t('quizzes.result.pointsAwarded', {
              awarded: format.number(question.points_awarded),
              points: format.number(question.points),
            })}
          </Badge>
        ) : (
          <span className="shrink-0 text-xs text-muted-foreground">{t('quizzes.points', { count: question.points, value: format.number(question.points) })}</span>
        )}
      </CardHeader>
      <CardContent>
        {question.type === 'essay' ? (
          <div>
            <p className="mb-1 text-xs text-muted-foreground">{t('quizzes.result.yourAnswer')}</p>
            <p dir="auto" className="whitespace-pre-line">
              {question.answer.text_answer || t('quizzes.result.noAnswer')}
            </p>
          </div>
        ) : (
          <ul className="space-y-2">
            {question.options.map((option) => {
              const mine = option.id === chosen
              const right = revealed && option.is_correct === true

              return (
                <li
                  key={option.id}
                  className={cn(
                    'flex items-center gap-3 rounded-md border px-3 py-2 text-sm',
                    mine && !revealed && 'border-primary bg-primary/5',
                    right && 'border-green-600 bg-green-50 dark:bg-green-950',
                    revealed && mine && !right && 'border-destructive bg-red-50 dark:bg-red-950',
                  )}
                >
                  {revealed && right ? (
                    <CircleCheck className="size-4 shrink-0 text-green-600" aria-label={t('quizzes.result.correctAnswer')} />
                  ) : revealed && mine ? (
                    <CircleX className="size-4 shrink-0 text-destructive" aria-label={t('quizzes.result.incorrect')} />
                  ) : (
                    <span className={cn('size-4 shrink-0 rounded-full border', mine && 'border-4 border-primary')} />
                  )}
                  <span dir="auto" className="flex-1">
                    {optionLabel(question, option.body, t)}
                  </span>
                  {mine && <span className="text-xs text-muted-foreground">{t('quizzes.result.yourAnswer')}</span>}
                </li>
              )
            })}
            {chosen == null && <li className="text-sm text-muted-foreground">{t('quizzes.result.noAnswer')}</li>}
          </ul>
        )}
      </CardContent>
    </Card>
  )
}
