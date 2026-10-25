import { CalendarClock, ClipboardList } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { useFormatters } from '@/hooks/useFormatters'
import { paths } from '@/routes/paths'
import type { AssignmentSummary } from '@/types/assignment'

/** The lesson's assignment, shown under the lesson content. */
export function AssignmentCard({ assignment }: { assignment: AssignmentSummary }) {
  const { t } = useTranslation()
  const format = useFormatters()

  return (
    <div className="flex flex-wrap items-center gap-4 rounded-lg border bg-muted/30 p-4">
      <ClipboardList className="size-8 shrink-0 text-primary" />
      <div className="min-w-0 flex-1 space-y-1">
        <p className="text-xs font-medium text-muted-foreground uppercase">{t('assignments.inLesson')}</p>
        <p dir="auto" className="font-semibold">
          {assignment.title}
        </p>
        <p className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
          <CalendarClock className="size-4" />
          {assignment.due_at ? t('assignments.due', { date: format.dateTime(assignment.due_at) }) : t('assignments.noDeadline')}
          {assignment.is_past_due && <Badge variant="destructive">{t('assignments.late')}</Badge>}
          <span>· {t('assignments.maxScore', { value: format.number(assignment.max_score) })}</span>
        </p>
      </div>
      <Button asChild>
        <Link to={paths.assignment(assignment.id)}>{t('assignments.open')}</Link>
      </Button>
    </div>
  )
}
