import axios from 'axios'
import { Star } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { useParams } from 'react-router'
import { ErrorState } from '@/components/ErrorState'
import { FullPageSpinner } from '@/components/FullPageSpinner'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { CourseCard, CourseGrid } from '@/features/courses/components/CourseCard'
import { useTeacher } from '@/features/teachers/hooks'
import { useFormatters } from '@/hooks/useFormatters'

export default function TeacherPage() {
  const { t } = useTranslation()
  const format = useFormatters()
  const { teacherId } = useParams()
  const { data: teacher, isPending, isError, error, refetch } = useTeacher(Number(teacherId))

  if (isPending) return <FullPageSpinner />

  if (isError) {
    const notFound = axios.isAxiosError(error) && error.response?.status === 404
    return <ErrorState message={notFound ? t('teachers.notFound') : undefined} onRetry={notFound ? undefined : () => void refetch()} />
  }

  return (
    <div className="mx-auto max-w-6xl space-y-10 px-4 py-10">
      <header className="flex flex-col items-center gap-6 text-center sm:flex-row sm:items-start sm:text-start">
        <Avatar className="size-28">
          {teacher.avatar_url && <AvatarImage src={teacher.avatar_url} alt="" />}
          <AvatarFallback className="text-4xl">{teacher.name.charAt(0)}</AvatarFallback>
        </Avatar>
        <div className="space-y-2">
          <h1 className="text-3xl font-bold">{teacher.name}</h1>
          {teacher.specialty && <p className="text-lg text-muted-foreground">{teacher.specialty}</p>}
          {/* Ratings come from course reviews (phase 6); hide until there are some. */}
          {teacher.rating > 0 && (
            <p className="flex items-center justify-center gap-1 text-sm sm:justify-start">
              <Star className="size-4 fill-yellow-400 text-yellow-400" />
              {format.number(teacher.rating)}
            </p>
          )}
          {teacher.bio && (
            <p dir="auto" className="max-w-2xl whitespace-pre-line text-muted-foreground">
              {teacher.bio}
            </p>
          )}
        </div>
      </header>

      <section className="space-y-4">
        <h2 className="text-xl font-bold">{t('teachers.courses', { name: teacher.name })}</h2>
        {teacher.courses.length === 0 ? (
          <p className="text-muted-foreground">{t('teachers.noCourses')}</p>
        ) : (
          <CourseGrid>
            {teacher.courses.map((course) => (
              <CourseCard key={course.id} course={course} />
            ))}
          </CourseGrid>
        )}
      </section>
    </div>
  )
}
