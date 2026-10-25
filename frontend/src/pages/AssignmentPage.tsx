import axios from 'axios'
import { ArrowLeft, CalendarClock, FileText, Lock, Trophy } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { Link, useParams } from 'react-router'
import { ErrorState } from '@/components/ErrorState'
import { FullPageSpinner } from '@/components/FullPageSpinner'
import { Markdown } from '@/components/Markdown'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { SubmissionForm, SubmissionStatus } from '@/features/assignments/components/SubmissionForm'
import { useAssignment } from '@/features/assignments/hooks'
import { useFormatters } from '@/hooks/useFormatters'
import { paths } from '@/routes/paths'
import type { Assignment, Submission } from '@/types/assignment'

export default function AssignmentPage() {
  const { t } = useTranslation()
  const { assignmentId } = useParams()
  const { data: assignment, isPending, isError, error, refetch } = useAssignment(Number(assignmentId))

  if (isPending) return <FullPageSpinner />

  if (isError) {
    const status = axios.isAxiosError(error) ? error.response?.status : undefined
    if (status === 403) return <ErrorState message={t('assignments.locked')} />
    if (status === 404) return <ErrorState message={t('assignments.notFound')} />
    return <ErrorState onRetry={() => void refetch()} />
  }

  return <AssignmentView assignment={assignment} />
}

function AssignmentView({ assignment }: { assignment: Assignment }) {
  const { t } = useTranslation()
  const format = useFormatters()
  const submission = assignment.submission ?? null

  return (
    <div className="mx-auto max-w-3xl space-y-6 px-4 py-8">
      <Link
        to={paths.lesson(assignment.lesson.id)}
        className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
      >
        <ArrowLeft className="size-4 rtl:rotate-180" />
        {t('assignments.backToLesson')}
      </Link>

      <header className="space-y-2">
        <p dir="auto" className="text-sm text-muted-foreground">
          {assignment.course.title} · {assignment.lesson.title}
        </p>
        <h1 dir="auto" className="text-3xl font-bold">
          {assignment.title}
        </h1>
        <p className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
          <CalendarClock className="size-4" />
          {assignment.due_at ? t('assignments.due', { date: format.dateTime(assignment.due_at) }) : t('assignments.noDeadline')}
          <span>· {t('assignments.maxScore', { value: format.number(assignment.max_score) })}</span>
        </p>
      </header>

      {assignment.is_past_due && !submission?.is_graded && (
        <Alert variant="destructive">
          <AlertDescription>{t('assignments.pastDue')}</AlertDescription>
        </Alert>
      )}

      {assignment.instructions && (
        <section>
          <h2 className="mb-1 text-lg font-semibold">{t('assignments.instructions')}</h2>
          <Markdown>{assignment.instructions}</Markdown>
        </section>
      )}

      <Card>
        <CardHeader>
          <CardTitle>{t('assignments.yourAnswer')}</CardTitle>
        </CardHeader>
        <CardContent>
          {submission?.is_graded ? (
            <GradedSubmission submission={submission} maxScore={assignment.max_score} />
          ) : (
            <SubmissionForm assignment={assignment} />
          )}
        </CardContent>
      </Card>
    </div>
  )
}

function GradedSubmission({ submission, maxScore }: { submission: Submission; maxScore: number }) {
  const { t } = useTranslation()
  const format = useFormatters()

  return (
    <div className="space-y-5">
      <div className="flex items-center gap-3 rounded-lg bg-green-50 p-4 text-green-900 dark:bg-green-950 dark:text-green-100">
        <Trophy className="size-6" />
        <div>
          <p className="text-sm">{t('assignments.graded')}</p>
          <p className="text-2xl font-bold" dir="ltr">
            {t('assignments.score', { score: format.number(submission.score ?? 0), max: format.number(maxScore) })}
          </p>
        </div>
      </div>

      {submission.feedback && (
        <div>
          <p className="mb-1 font-medium">{t('assignments.feedback')}</p>
          <p dir="auto" className="whitespace-pre-line text-muted-foreground">
            {submission.feedback}
          </p>
        </div>
      )}

      <div className="space-y-2 border-t pt-4">
        <SubmissionStatus submission={submission} />
        {submission.content && (
          <p dir="auto" className="whitespace-pre-line">
            {submission.content}
          </p>
        )}
        {submission.file && (
          <a
            href={submission.file.url}
            target="_blank"
            // No "noreferrer": the API needs the Referer to recognise the signed-in session.
            rel="noopener"
            className="inline-flex items-center gap-2 text-sm text-primary underline-offset-4 hover:underline"
            dir="auto"
          >
            <FileText className="size-4" />
            {submission.file.name}
          </a>
        )}
        <p className="flex items-center gap-1 text-xs text-muted-foreground">
          <Lock className="size-3" />
          {t('assignments.lockedAfterGrading')}
        </p>
      </div>
    </div>
  )
}
