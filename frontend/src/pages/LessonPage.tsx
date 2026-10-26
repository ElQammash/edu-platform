import axios from 'axios'
import { ArrowLeft, ArrowRight, CircleCheck, Lock } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { Link, useParams } from 'react-router'
import { ErrorState } from '@/components/ErrorState'
import { FullPageSpinner } from '@/components/FullPageSpinner'
import { Markdown } from '@/components/Markdown'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { useAuth } from '@/features/auth/hooks'
import { AssignmentCard } from '@/features/assignments/components/AssignmentCard'
import { QuizCard } from '@/features/quizzes/components/QuizCard'
import { CourseCurriculum } from '@/features/courses/components/CourseCurriculum'
import { useCourse } from '@/features/courses/hooks'
import { VideoPlayer } from '@/features/lessons/components/VideoPlayer'
import { useCompleteLesson, useLesson, useRecordProgress } from '@/features/lessons/hooks'
import { useWatchProgress } from '@/features/lessons/useWatchProgress'
import { LiveLessonPanel } from '@/features/live/components/LiveLessonPanel'
import { LessonQa } from '@/features/qa/components/LessonQa'
import { paths } from '@/routes/paths'
import type { Lesson } from '@/types/course'

export default function LessonPage() {
  const { t } = useTranslation()
  const { lessonId } = useParams()
  const { data: lesson, isPending, isError, error, refetch } = useLesson(Number(lessonId))

  if (isPending) return <FullPageSpinner />

  if (isError) {
    const status = axios.isAxiosError(error) ? error.response?.status : undefined

    if (status === 401 || status === 403) return <LockedLesson />
    if (status === 404) return <ErrorState message={t('lessons.notFound')} />
    return <ErrorState onRetry={() => void refetch()} />
  }

  // Remount per lesson so player and progress state start fresh.
  return <LessonView key={lesson.id} lesson={lesson} />
}

function LessonView({ lesson }: { lesson: Lesson }) {
  const { t } = useTranslation()
  const { user, hasRole } = useAuth()
  const { data: course } = useCourse(lesson.course.slug)

  // The API only includes `progress` for enrolled students.
  const tracksProgress = lesson.progress !== undefined
  const isCompleted = lesson.progress?.is_completed ?? false
  const isStaff = user !== null && !hasRole('student')

  const record = useRecordProgress(lesson.id, lesson.course.slug)
  const complete = useCompleteLesson(lesson.id, lesson.course.slug)
  const playback = useWatchProgress(tracksProgress && !isCompleted, (watched, duration) =>
    record.mutate({ watched, duration }),
  )

  return (
    <div className="mx-auto max-w-7xl px-4 py-6">
      <div className="grid gap-6 lg:grid-cols-[1fr_340px]">
        <div className="min-w-0 space-y-5">
          <Link
            to={paths.courseDetails(lesson.course.slug)}
            className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
          >
            <ArrowLeft className="size-4 rtl:rotate-180" />
            <span dir="auto">{lesson.course.title}</span>
          </Link>

          {!tracksProgress && !isStaff && lesson.is_free_preview && (
            <Alert>
              <AlertDescription className="flex flex-wrap items-center justify-between gap-2">
                {t('lessons.previewNotice')}
                <Link to={paths.courseDetails(lesson.course.slug)} className="font-medium underline underline-offset-4">
                  {t('lessons.goToCourse')}
                </Link>
              </AlertDescription>
            </Alert>
          )}

          {lesson.live && <LiveLessonPanel lesson={lesson} tracksAttendance={tracksProgress} />}

          {/* A video lesson, or a live lesson's recording once it has ended. */}
          {lesson.video ? (
            <div className="space-y-2">
              {lesson.live && <p className="text-sm font-medium">{t('live.recording')}</p>}
              <VideoPlayer video={lesson.video} title={lesson.title} events={playback} />
            </div>
          ) : (
            lesson.type === 'video' && (
              <div className="flex aspect-video items-center justify-center rounded-lg bg-muted text-muted-foreground">
                {t('lessons.noVideo')}
              </div>
            )
          )}

          <header className="space-y-1">
            <p dir="auto" className="text-sm text-muted-foreground">
              {lesson.section.title}
            </p>
            <h1 dir="auto" className="text-2xl font-bold">
              {lesson.title}
            </h1>
          </header>

          <Markdown>{lesson.content}</Markdown>

          {lesson.assignment && <AssignmentCard assignment={lesson.assignment} />}
          {lesson.quiz && <QuizCard quiz={lesson.quiz} />}

          <footer className="flex flex-wrap items-center gap-3 border-t pt-5">
            {tracksProgress &&
              (isCompleted ? (
                <span className="inline-flex items-center gap-2 font-medium text-green-700">
                  <CircleCheck className="size-5" />
                  {t('lessons.completed')}
                </span>
              ) : (
                <div className="space-y-1">
                  <Button disabled={complete.isPending} onClick={() => complete.mutate()}>
                    <CircleCheck />
                    {complete.isPending ? t('lessons.marking') : t('lessons.markComplete')}
                  </Button>
                  {lesson.type === 'video' && <p className="text-xs text-muted-foreground">{t('lessons.autoCompleteHint')}</p>}
                </div>
              ))}

            <div className="ms-auto flex gap-2">
              {lesson.previous_lesson_id && (
                <Button asChild variant="outline">
                  <Link to={paths.lesson(lesson.previous_lesson_id)}>
                    <ArrowLeft className="rtl:rotate-180" />
                    {t('lessons.previous')}
                  </Link>
                </Button>
              )}
              {lesson.next_lesson_id && (
                <Button asChild variant="outline">
                  <Link to={paths.lesson(lesson.next_lesson_id)}>
                    {t('lessons.next')}
                    <ArrowRight className="rtl:rotate-180" />
                  </Link>
                </Button>
              )}
            </div>
          </footer>

          {user && <LessonQa lessonId={lesson.id} />}
        </div>

        <aside className="space-y-3 lg:sticky lg:top-6 lg:max-h-[calc(100svh-3rem)] lg:self-start lg:overflow-y-auto">
          <h2 className="font-semibold">{t('courses.details.curriculum')}</h2>
          {course?.sections && (
            <CourseCurriculum
              sections={course.sections}
              canOpenAll={Boolean(course.enrollment) || isStaff}
              currentLessonId={lesson.id}
              compact
            />
          )}
        </aside>
      </div>
    </div>
  )
}

function LockedLesson() {
  const { t } = useTranslation()
  const { user } = useAuth()

  return (
    <div className="mx-auto flex max-w-md flex-col items-center gap-4 px-4 py-24 text-center">
      <Lock className="size-10 text-muted-foreground" />
      <h1 className="text-2xl font-bold">{t('lessons.lockedTitle')}</h1>
      <p className="text-muted-foreground">{t('lessons.lockedHint')}</p>
      <div className="flex gap-2">
        {!user && (
          <Button asChild>
            <Link to={paths.login} state={{ from: window.location.pathname }}>
              {t('pages.login.title')}
            </Link>
          </Button>
        )}
        <Button asChild variant="outline">
          <Link to={paths.catalog}>{t('pages.catalog.title')}</Link>
        </Button>
      </div>
    </div>
  )
}
