import { CircleCheck, FileText, Lock, PlayCircle, Radio } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { Badge } from '@/components/ui/badge'
import { useFormatters } from '@/hooks/useFormatters'
import { cn } from '@/lib/utils'
import { paths } from '@/routes/paths'
import type { LessonSummary, LessonType, Section } from '@/types/course'

const icons: Record<LessonType, typeof PlayCircle> = {
  video: PlayCircle,
  text: FileText,
  live: Radio,
}

/**
 * Sections and lessons of a course. Lessons the viewer can open are links;
 * the rest show a lock. Used on the course page and beside the player.
 */
export function CourseCurriculum({
  sections,
  canOpenAll,
  currentLessonId,
  compact = false,
}: {
  sections: Section[]
  /** Enrolled students and staff can open every lesson; others only free previews. */
  canOpenAll: boolean
  currentLessonId?: number
  compact?: boolean
}) {
  const { t } = useTranslation()
  const format = useFormatters()

  return (
    <div className="divide-y rounded-lg border">
      {sections.map((section) => (
        <section key={section.id}>
          <h3 dir="auto" className={cn('bg-muted/50 px-4 font-semibold', compact ? 'py-2 text-sm' : 'py-3')}>
            {section.title}
          </h3>
          <ul className="divide-y">
            {section.lessons.map((lesson) => (
              <LessonRow
                key={lesson.id}
                lesson={lesson}
                canOpen={canOpenAll || lesson.is_free_preview}
                isCurrent={lesson.id === currentLessonId}
                compact={compact}
                duration={format.duration(lesson.duration_seconds)}
                labels={{
                  preview: t('courses.details.freePreview'),
                  locked: t('courses.details.locked'),
                  completed: t('courses.details.completed'),
                }}
              />
            ))}
          </ul>
        </section>
      ))}
    </div>
  )
}

function LessonRow({
  lesson,
  canOpen,
  isCurrent,
  compact,
  duration,
  labels,
}: {
  lesson: LessonSummary
  canOpen: boolean
  isCurrent: boolean
  compact: boolean
  duration: string
  labels: { preview: string; locked: string; completed: string }
}) {
  const Icon = icons[lesson.type]
  const body = (
    <>
      {lesson.is_completed ? (
        <CircleCheck className="size-4 shrink-0 text-green-600" aria-label={labels.completed} />
      ) : (
        <Icon className="size-4 shrink-0 text-muted-foreground" />
      )}
      <span dir="auto" className="flex-1">
        {lesson.title}
      </span>
      {lesson.is_free_preview && !compact && <Badge variant="outline">{labels.preview}</Badge>}
      {duration && <span className="text-xs text-muted-foreground">{duration}</span>}
      {!canOpen && <Lock className="size-4 shrink-0 text-muted-foreground" aria-label={labels.locked} />}
    </>
  )

  const classes = cn(
    'flex items-center gap-3 px-4 text-sm',
    compact ? 'py-2' : 'py-3',
    isCurrent && 'bg-accent font-medium',
  )

  return (
    <li>
      {canOpen ? (
        <Link
          to={paths.lesson(lesson.id)}
          className={cn(classes, 'hover:bg-accent/60')}
          aria-current={isCurrent ? 'page' : undefined}
        >
          {body}
        </Link>
      ) : (
        <div className={cn(classes, 'text-muted-foreground')}>{body}</div>
      )}
    </li>
  )
}
