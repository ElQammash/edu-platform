import { CalendarPlus, Radio } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { useLiveSessions } from '@/features/live/hooks'
import { useFormatters } from '@/hooks/useFormatters'
import { paths } from '@/routes/paths'

/** The next live sessions across the student's courses (hidden when there are none). */
export function UpcomingLiveSessions() {
  const { t } = useTranslation()
  const format = useFormatters()
  const { data: sessions } = useLiveSessions()

  if (!sessions || sessions.length === 0) return null

  return (
    <Card>
      <CardHeader>
        <CardTitle>{t('live.upcomingTitle')}</CardTitle>
      </CardHeader>
      <CardContent className="divide-y p-0">
        {sessions.slice(0, 5).map((session) => (
          <div key={session.lesson.id} className="flex flex-wrap items-center gap-3 px-6 py-3 text-sm">
            {session.state === 'live' ? (
              <Badge className="gap-1 bg-red-600 text-white">
                <Radio className="size-3 animate-pulse" />
                {t('live.liveNow')}
              </Badge>
            ) : (
              <Badge variant="secondary">{format.dateTime(session.starts_at)}</Badge>
            )}
            <div className="min-w-0 flex-1">
              <p dir="auto" className="truncate font-medium">
                {session.lesson.title}
              </p>
              <p dir="auto" className="truncate text-xs text-muted-foreground">
                {session.course.title}
              </p>
            </div>
            <Button asChild variant="ghost" size="sm">
              <a href={session.calendar_url} download aria-label={t('live.addToCalendar')}>
                <CalendarPlus />
              </a>
            </Button>
            <Button asChild size="sm" variant={session.state === 'live' ? 'default' : 'outline'}>
              <Link to={paths.lesson(session.lesson.id)}>{t('live.openLesson')}</Link>
            </Button>
          </div>
        ))}
      </CardContent>
    </Card>
  )
}
