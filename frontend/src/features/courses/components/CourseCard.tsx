import { BookOpen, GraduationCap } from 'lucide-react'
import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardFooter } from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'
import { Skeleton } from '@/components/ui/skeleton'
import { useFormatters } from '@/hooks/useFormatters'
import { paths } from '@/routes/paths'
import type { Course } from '@/types/course'

/**
 * A course in a grid. With `progress`, shows the student's completion
 * (My courses) instead of the price.
 */
export function CourseCard({ course, progress }: { course: Course; progress?: number }) {
  const { t } = useTranslation()
  const format = useFormatters()

  return (
    <Card className="group gap-0 overflow-hidden py-0 transition-shadow hover:shadow-md">
      <Link to={paths.courseDetails(course.slug)} className="flex flex-1 flex-col focus-visible:outline-none">
        <div className="relative aspect-video bg-muted">
          {course.thumbnail_url ? (
            <img src={course.thumbnail_url} alt="" className="size-full object-cover" loading="lazy" />
          ) : (
            <div className="flex size-full items-center justify-center text-muted-foreground">
              <GraduationCap className="size-12" />
            </div>
          )}
          <div className="absolute top-2 start-2 flex gap-1">
            <Badge variant="secondary">{t(`courses.language.${course.language}`)}</Badge>
            {course.type !== 'recorded' && <Badge>{t(`courses.type.${course.type}`)}</Badge>}
          </div>
        </div>

        <CardContent className="flex flex-1 flex-col gap-2 p-4">
          {/* The title keeps its own direction (an English course on an Arabic page). */}
          <h3 dir="auto" className="line-clamp-2 font-semibold group-hover:underline">
            {course.title}
          </h3>
          {course.teacher && (
            <p className="text-sm text-muted-foreground">{t('courses.card.by', { name: course.teacher.name })}</p>
          )}
          {course.lessons_count !== undefined && (
            <p className="mt-auto flex items-center gap-1 text-xs text-muted-foreground">
              <BookOpen className="size-3.5" />
              {t('courses.card.lessons', { value: format.number(course.lessons_count) })}
            </p>
          )}
        </CardContent>

        <CardFooter className="border-t p-4">
          {progress === undefined ? (
            <span className="font-semibold">{format.price(course.price)}</span>
          ) : (
            <div className="w-full space-y-1">
              <Progress value={progress} aria-label={t('courses.enroll.progress', { value: progress })} />
              <p className="text-xs text-muted-foreground">
                {t('courses.enroll.progress', { value: format.number(progress) })}
              </p>
            </div>
          )}
        </CardFooter>
      </Link>
    </Card>
  )
}

export function CourseCardSkeleton() {
  return (
    <Card className="gap-0 overflow-hidden py-0">
      <Skeleton className="aspect-video rounded-none" />
      <div className="space-y-2 p-4">
        <Skeleton className="h-5 w-4/5" />
        <Skeleton className="h-4 w-1/2" />
      </div>
      <div className="border-t p-4">
        <Skeleton className="h-5 w-16" />
      </div>
    </Card>
  )
}

export function CourseGrid({ children }: { children: ReactNode }) {
  return <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">{children}</div>
}
