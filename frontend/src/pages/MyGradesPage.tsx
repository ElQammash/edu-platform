import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { ErrorState } from '@/components/ErrorState'
import { FullPageSpinner } from '@/components/FullPageSpinner'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'
import { useMyGrades } from '@/features/grades/hooks'
import { useFormatters } from '@/hooks/useFormatters'
import { cn } from '@/lib/utils'
import { paths } from '@/routes/paths'
import type { ComponentGrade, CourseGrades } from '@/types/quiz'

export default function MyGradesPage() {
  const { t } = useTranslation()
  const { data: courses, isPending, isError, refetch } = useMyGrades()

  if (isPending) return <FullPageSpinner />
  if (isError) return <ErrorState onRetry={() => void refetch()} />

  return (
    <div className="mx-auto max-w-5xl space-y-6 px-4 py-10">
      <header className="space-y-1">
        <h1 className="text-3xl font-bold">{t('pages.myGrades.title')}</h1>
        <p className="text-muted-foreground">{t('grades.subtitle')}</p>
      </header>

      {courses.length === 0 ? (
        <p className="py-16 text-center text-muted-foreground">{t('grades.empty')}</p>
      ) : (
        courses.map((course) => <CourseGradeCard key={course.course.id} grades={course} />)
      )}

      <p className="text-xs text-muted-foreground">{t('grades.rules')}</p>
    </div>
  )
}

function CourseGradeCard({ grades }: { grades: CourseGrades }) {
  const { t } = useTranslation()
  const format = useFormatters()

  return (
    <Card>
      <CardHeader className="flex-row flex-wrap items-center gap-4">
        <CardTitle dir="auto" className="me-auto text-lg">
          <Link to={paths.courseDetails(grades.course.slug)} className="hover:underline">
            {grades.course.title}
          </Link>
        </CardTitle>
        {grades.total > 0 && (
          <div className="w-48 space-y-1">
            <Progress value={(grades.done / grades.total) * 100} />
            <p className="text-xs text-muted-foreground">
              {t('grades.done', { done: format.number(grades.done), total: format.number(grades.total) })}
            </p>
          </div>
        )}
        <div className="text-end">
          <p className="text-xs text-muted-foreground">{t('grades.courseGrade')}</p>
          <p className="text-2xl font-bold" dir="ltr">
            {grades.percent === null ? '—' : `${format.number(grades.percent)}%`}
          </p>
        </div>
      </CardHeader>
      <CardContent className="p-0">
        {grades.lessons.length === 0 ? (
          <p className="px-6 pb-6 text-sm text-muted-foreground">{t('grades.noAssessments')}</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead className="border-y bg-muted/40 text-xs text-muted-foreground">
                <tr>
                  <th className="px-6 py-2 text-start font-medium">{t('grades.lesson')}</th>
                  <th className="px-3 py-2 text-start font-medium">{t('grades.assignment')}</th>
                  <th className="px-3 py-2 text-start font-medium">{t('grades.quiz')}</th>
                  <th className="px-6 py-2 text-end font-medium">{t('grades.lessonGrade')}</th>
                </tr>
              </thead>
              <tbody className="divide-y">
                {grades.lessons.map((row) => (
                  <tr key={row.lesson.id}>
                    <td className="px-6 py-3">
                      <Link to={paths.lesson(row.lesson.id)} dir="auto" className="hover:underline">
                        {row.lesson.title}
                      </Link>
                    </td>
                    <td className="px-3 py-3">
                      <Component grade={row.assignment} href={row.assignment?.id ? paths.assignment(row.assignment.id) : undefined} />
                    </td>
                    <td className="px-3 py-3">
                      <Component grade={row.quiz} href={row.quiz?.id ? paths.quiz(row.quiz.id) : undefined} />
                    </td>
                    <td className="px-6 py-3 text-end font-semibold" dir="ltr">
                      {row.percent === null ? '—' : `${format.number(row.percent)}%`}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </CardContent>
    </Card>
  )
}

function Component({ grade, href }: { grade: ComponentGrade | null; href?: string }) {
  const { t } = useTranslation()
  const format = useFormatters()

  if (!grade) return <span className="text-muted-foreground">—</span>

  const label =
    grade.status === 'graded' && grade.percent !== null ? `${format.number(grade.percent)}%` : t(`grades.status.${grade.status}`)

  const badge = (
    <Badge
      variant={grade.status === 'missing' ? 'destructive' : grade.status === 'graded' ? 'default' : 'secondary'}
      className={cn(grade.status === 'open' && 'bg-transparent text-muted-foreground')}
      dir={grade.status === 'graded' ? 'ltr' : undefined}
    >
      {label}
    </Badge>
  )

  return href ? <Link to={href}>{badge}</Link> : badge
}
