import axios from 'axios'
import { BookOpen, Clock, GraduationCap, Languages, Star, Users } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { Link, useParams } from 'react-router'
import { ErrorState } from '@/components/ErrorState'
import { FullPageSpinner } from '@/components/FullPageSpinner'
import { Markdown } from '@/components/Markdown'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import { useAuth } from '@/features/auth/hooks'
import { CourseCurriculum } from '@/features/courses/components/CourseCurriculum'
import { EnrollPanel } from '@/features/courses/components/EnrollPanel'
import { useCourse } from '@/features/courses/hooks'
import { CourseReviews } from '@/features/reviews/components/CourseReviews'
import { useFormatters } from '@/hooks/useFormatters'
import { paths } from '@/routes/paths'

export default function CourseDetailsPage() {
  const { t } = useTranslation()
  const format = useFormatters()
  const { slug = '' } = useParams()
  const { user, hasRole } = useAuth()
  const { data: course, isPending, isError, error, refetch } = useCourse(slug)

  if (isPending) return <FullPageSpinner />

  if (isError) {
    const notFound = axios.isAxiosError(error) && [403, 404].includes(error.response?.status ?? 0)
    return <ErrorState message={notFound ? t('courses.details.notFound') : undefined} onRetry={notFound ? undefined : () => void refetch()} />
  }

  // Staff open every lesson to review it; students need an enrollment.
  const canOpenAll = Boolean(course.enrollment) || (user !== null && !hasRole('student'))
  const teacher = course.teacher

  return (
    <div className="mx-auto max-w-6xl px-4 py-10">
      <div className="grid gap-8 lg:grid-cols-[1fr_360px]">
        <div className="space-y-8">
          <header className="space-y-4">
            <div className="flex flex-wrap gap-2">
              <Badge variant="secondary">
                <Languages />
                {t('courses.details.taughtIn', { language: t(`courses.language.${course.language}`) })}
              </Badge>
              <Badge variant="outline">{t(`courses.type.${course.type}`)}</Badge>
            </div>
            <h1 dir="auto" className="text-3xl font-bold">
              {course.title}
            </h1>
            <p dir="auto" className="text-lg text-muted-foreground">
              {course.excerpt}
            </p>
            <ul className="flex flex-wrap gap-x-6 gap-y-2 text-sm text-muted-foreground">
              {course.rating.count > 0 && (
                <li>
                  <a href="#reviews" className="flex items-center gap-1.5 hover:text-foreground">
                    <Star className="size-4 fill-amber-500 text-amber-500" />
                    <span className="font-semibold text-foreground">{format.number(course.rating.average)}</span>
                    {t('reviews.count', { count: course.rating.count, value: format.number(course.rating.count) })}
                  </a>
                </li>
              )}
              <li className="flex items-center gap-1.5">
                <BookOpen className="size-4" />
                {t('courses.details.lessons', { count: course.lessons_count ?? 0, value: format.number(course.lessons_count ?? 0) })}
              </li>
              {!!course.total_duration_seconds && (
                <li className="flex items-center gap-1.5">
                  <Clock className="size-4" />
                  {format.duration(course.total_duration_seconds)}
                </li>
              )}
              <li className="flex items-center gap-1.5">
                <Users className="size-4" />
                {t('courses.details.students', { count: course.students_count ?? 0, value: format.number(course.students_count ?? 0) })}
              </li>
            </ul>
          </header>

          {course.description && (
            <section>
              <h2 className="mb-2 text-xl font-bold">{t('courses.details.about')}</h2>
              <Markdown>{course.description}</Markdown>
            </section>
          )}

          <section className="space-y-3">
            <h2 className="text-xl font-bold">{t('courses.details.curriculum')}</h2>
            <CourseCurriculum sections={course.sections ?? []} canOpenAll={canOpenAll} />
          </section>

          {teacher && (
            <section className="space-y-3">
              <h2 className="text-xl font-bold">{t('courses.details.aboutTeacher')}</h2>
              <Link
                to={paths.teacher(teacher.id)}
                className="flex items-center gap-4 rounded-lg border p-4 transition-colors hover:bg-accent/60"
              >
                <Avatar className="size-14">
                  {teacher.avatar_url && <AvatarImage src={teacher.avatar_url} alt="" />}
                  <AvatarFallback>{teacher.name.charAt(0)}</AvatarFallback>
                </Avatar>
                <div className="flex-1">
                  <p className="font-semibold">{teacher.name}</p>
                  {teacher.specialty && <p className="text-sm text-muted-foreground">{teacher.specialty}</p>}
                </div>
                <span className="text-sm font-medium text-primary">{t('courses.details.viewTeacher')}</span>
              </Link>
            </section>
          )}

          <CourseReviews course={course} />
        </div>

        <aside className="lg:sticky lg:top-6 lg:self-start">
          <Card className="gap-0 overflow-hidden py-0">
            <div className="aspect-video bg-muted">
              {course.thumbnail_url ? (
                <img src={course.thumbnail_url} alt="" className="size-full object-cover" />
              ) : (
                <div className="flex size-full items-center justify-center text-muted-foreground">
                  <GraduationCap className="size-16" />
                </div>
              )}
            </div>
            <CardContent className="space-y-4 p-6">
              {!course.enrollment && <p className="text-3xl font-bold">{format.price(course.price)}</p>}
              <EnrollPanel course={course} />
            </CardContent>
          </Card>
        </aside>
      </div>
    </div>
  )
}
