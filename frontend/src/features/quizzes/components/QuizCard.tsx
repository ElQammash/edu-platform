import { CalendarClock, ListChecks, Timer } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { useFormatters } from '@/hooks/useFormatters'
import { paths } from '@/routes/paths'
import type { QuizSummary } from '@/types/quiz'

/** The lesson's quiz, shown under the lesson content. */
export function QuizCard({ quiz }: { quiz: QuizSummary }) {
  const { t } = useTranslation()
  const format = useFormatters()

  return (
    <div className="flex flex-wrap items-center gap-4 rounded-lg border bg-muted/30 p-4">
      <ListChecks className="size-8 shrink-0 text-primary" />
      <div className="min-w-0 flex-1 space-y-1">
        <p className="text-xs font-medium text-muted-foreground uppercase">{t('quizzes.inLesson')}</p>
        <p dir="auto" className="font-semibold">
          {quiz.title}
        </p>
        <p className="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted-foreground">
          <span>{t('quizzes.questions', { count: quiz.questions_count, value: format.number(quiz.questions_count) })}</span>
          <span className="flex items-center gap-1">
            <Timer className="size-4" />
            {quiz.time_limit_minutes ? t('quizzes.timeLimit', { value: format.number(quiz.time_limit_minutes) }) : t('quizzes.untimed')}
          </span>
          {quiz.due_at && (
            <span className="flex items-center gap-1">
              <CalendarClock className="size-4" />
              {t('quizzes.due', { date: format.dateTime(quiz.due_at) })}
            </span>
          )}
          {quiz.is_past_due && <Badge variant="destructive">{t('assignments.late')}</Badge>}
        </p>
      </div>
      <Button asChild>
        <Link to={paths.quiz(quiz.id)}>{t('quizzes.open')}</Link>
      </Button>
    </div>
  )
}
