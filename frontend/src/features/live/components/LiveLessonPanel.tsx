import { useQueryClient } from '@tanstack/react-query'
import { CalendarPlus, Radio, Video } from 'lucide-react'
import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { courseKeys } from '@/features/courses/hooks'
import { lessonKeys } from '@/features/lessons/hooks'
import { calendarUrl } from '@/features/live/api'
import { useJoinLesson } from '@/features/live/hooks'
import { formatClock } from '@/features/quizzes/useCountdown'
import { useFormatters } from '@/hooks/useFormatters'
import { useServerClock } from '@/hooks/useServerClock'
import { getGeneralError } from '@/lib/apiErrors'
import type { Lesson, LiveState } from '@/types/course'

const JOIN_OPENS_MINUTES_BEFORE = 15

/**
 * A live lesson's schedule and Join button. The state follows the server's
 * clock; the meeting link is fetched only when joining (inside the window),
 * which also records the student's attendance.
 */
export function LiveLessonPanel({ lesson, tracksAttendance }: { lesson: Lesson; tracksAttendance: boolean }) {
  const { t } = useTranslation()
  const format = useFormatters()
  const queryClient = useQueryClient()
  const join = useJoinLesson(lesson.id)
  const live = lesson.live!
  const now = useServerClock(live.server_time)
  const [joinError, setJoinError] = useState<string | null>(null)

  const opensAt = Date.parse(live.join_opens_at)
  const startsAt = Date.parse(live.starts_at)
  const endsAt = Date.parse(live.ends_at)

  const state: LiveState = now === null ? live.state : now >= endsAt ? 'ended' : now >= startsAt ? 'live' : 'upcoming'
  const canJoin = now === null ? live.can_join : now >= opensAt && now < endsAt

  // When the session ends, reload the lesson to pick up its recording.
  useEffect(() => {
    if (state === 'ended' && live.state !== 'ended') {
      void queryClient.invalidateQueries({ queryKey: lessonKeys.detail(lesson.id) })
    }
  }, [state, live.state, lesson.id, queryClient])

  function handleJoin() {
    setJoinError(null)
    // Open the tab now, inside the click, so popup blockers allow it.
    const tab = window.open('', '_blank')

    join.mutate(undefined, {
      onSuccess: (url) => {
        if (tab) {
          tab.opener = null
          tab.location.href = url
        } else {
          window.location.href = url
        }
        if (tracksAttendance) void queryClient.invalidateQueries({ queryKey: courseKeys.detail(lesson.course.slug) })
      },
      onError: (error) => {
        tab?.close()
        setJoinError(getGeneralError(error, t('auth.errors.generic')))
      },
    })
  }

  return (
    <div className="space-y-4 rounded-lg border p-5">
      <div className="flex flex-wrap items-center gap-3">
        {state === 'live' ? (
          <Badge className="gap-1 bg-red-600 text-white">
            <Radio className="size-3 animate-pulse" />
            {t('live.liveNow')}
          </Badge>
        ) : (
          <Badge variant="secondary">{t('live.badge')}</Badge>
        )}
        {state !== 'live' && <span className="font-medium">{state === 'ended' ? t('live.ended') : t('live.upcoming')}</span>}
      </div>

      <div className="space-y-1 text-sm text-muted-foreground">
        <p>{t('live.startsAt', { date: format.dateTime(live.starts_at) })}</p>
        <p>{t('live.duration', { value: format.duration((endsAt - startsAt) / 1000) })}</p>
      </div>

      {state !== 'ended' && (
        <div className="flex flex-wrap items-center gap-3">
          <Button size="lg" disabled={!canJoin || join.isPending} onClick={handleJoin}>
            <Video />
            {join.isPending ? t('live.joining') : t('live.join')}
          </Button>
          <Button asChild variant="outline">
            <a href={calendarUrl(lesson.id)} download>
              <CalendarPlus />
              {t('live.addToCalendar')}
            </a>
          </Button>
        </div>
      )}

      {state === 'upcoming' && now !== null && (
        <p className="text-sm text-muted-foreground">
          {canJoin ? (
            <>
              {t('live.startsIn')}{' '}
              <span className="font-mono tabular-nums" dir="ltr">
                {formatClock(startsAt - now)}
              </span>
            </>
          ) : (
            t('live.joinOpensIn', { time: untilLabel(opensAt - now, format.number, t) })
          )}
        </p>
      )}

      {state !== 'ended' && <p className="text-xs text-muted-foreground">{t('live.joinHint', { minutes: format.number(JOIN_OPENS_MINUTES_BEFORE) })}</p>}

      {state === 'ended' && !live.has_recording && <p className="text-sm text-muted-foreground">{t('live.noRecording')}</p>}

      {joinError && (
        <Alert variant="destructive">
          <AlertDescription>{joinError}</AlertDescription>
        </Alert>
      )}
    </div>
  )
}

/** "2 d" for long waits, otherwise a clock (h:mm:ss). */
function untilLabel(ms: number, number: (n: number) => string, t: (key: 'live.days', vars: { value: string }) => string): string {
  const days = Math.floor(ms / 86_400_000)
  return days >= 1 ? t('live.days', { value: number(days) }) : formatClock(ms)
}
