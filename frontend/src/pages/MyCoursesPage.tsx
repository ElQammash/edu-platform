import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { ErrorState } from '@/components/ErrorState'
import { Button } from '@/components/ui/button'
import { CourseCard, CourseCardSkeleton, CourseGrid } from '@/features/courses/components/CourseCard'
import { useMyCourses } from '@/features/courses/hooks'
import { UpcomingLiveSessions } from '@/features/live/components/UpcomingLiveSessions'
import { paths } from '@/routes/paths'

export default function MyCoursesPage() {
  const { t } = useTranslation()
  const { data: enrollments, isPending, isError, refetch } = useMyCourses()

  return (
    <div className="mx-auto max-w-6xl space-y-6 px-4 py-10">
      <header className="space-y-1">
        <h1 className="text-3xl font-bold">{t('pages.myCourses.title')}</h1>
        <p className="text-muted-foreground">{t('myCourses.subtitle')}</p>
      </header>

      <UpcomingLiveSessions />

      {isError ? (
        <ErrorState onRetry={() => void refetch()} />
      ) : isPending ? (
        <CourseGrid>
          {Array.from({ length: 4 }, (_, i) => (
            <CourseCardSkeleton key={i} />
          ))}
        </CourseGrid>
      ) : enrollments.length === 0 ? (
        <div className="flex flex-col items-center gap-4 py-16 text-center">
          <p className="text-muted-foreground">{t('myCourses.empty')}</p>
          <Button asChild>
            <Link to={paths.catalog}>{t('myCourses.browse')}</Link>
          </Button>
        </div>
      ) : (
        <CourseGrid>
          {enrollments.map(
            (enrollment) =>
              enrollment.course && (
                <CourseCard key={enrollment.id} course={enrollment.course} progress={enrollment.progress_percent} />
              ),
          )}
        </CourseGrid>
      )}
    </div>
  )
}
