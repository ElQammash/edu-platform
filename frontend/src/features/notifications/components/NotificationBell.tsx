import { Bell } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { useNavigate } from 'react-router'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { useMarkAllNotificationsRead, useMarkNotificationRead, useNotifications } from '@/features/notifications/hooks'
import { useFormatters } from '@/hooks/useFormatters'
import { cn } from '@/lib/utils'
import { paths } from '@/routes/paths'
import type { AppNotification } from '@/types/assignment'

/**
 * In-app notifications for students (e.g. "your submission was graded").
 * The text is built here from the notification's data, so it follows the
 * interface language.
 */
export function NotificationBell() {
  const { t } = useTranslation()
  const format = useFormatters()
  const navigate = useNavigate()
  const { data } = useNotifications(true)
  const markRead = useMarkNotificationRead()
  const markAllRead = useMarkAllNotificationsRead()

  const notifications = data?.data ?? []
  const unread = data?.meta.unread_count ?? 0

  function open(notification: AppNotification) {
    if (!notification.read_at) markRead.mutate(notification.id)
    navigate(
      notification.type === 'quiz_graded'
        ? paths.attempt(notification.data.attempt_id)
        : paths.assignment(notification.data.assignment_id),
    )
  }

  function message(notification: AppNotification): string {
    const score = format.number(notification.data.score)
    const max = format.number(notification.data.max_score)

    if (notification.type === 'quiz_graded') {
      return t(notification.data.regraded ? 'notifications.quizRegraded' : 'notifications.quizGraded', {
        quiz: notification.data.quiz_title,
        score,
        max,
      })
    }

    return t(notification.data.regraded ? 'notifications.regraded' : 'notifications.graded', {
      assignment: notification.data.assignment_title,
      score,
      max,
    })
  }

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button
          variant="ghost"
          size="icon-sm"
          className="relative"
          aria-label={unread > 0 ? t('notifications.unread', { count: unread }) : t('notifications.title')}
        >
          <Bell />
          {unread > 0 && (
            <span className="absolute -top-0.5 -end-0.5 flex min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[10px] leading-4 font-semibold text-white">
              {format.number(Math.min(unread, 9))}
              {unread > 9 && '+'}
            </span>
          )}
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-80">
        <div className="flex items-center justify-between gap-2 pe-1">
          <DropdownMenuLabel>{t('notifications.title')}</DropdownMenuLabel>
          {unread > 0 && (
            <Button variant="link" size="sm" className="h-auto p-0 text-xs" onClick={() => markAllRead.mutate()}>
              {t('notifications.markAllRead')}
            </Button>
          )}
        </div>
        <DropdownMenuSeparator />
        {notifications.length === 0 ? (
          <p className="px-2 py-6 text-center text-sm text-muted-foreground">{t('notifications.empty')}</p>
        ) : (
          <div className="max-h-96 overflow-y-auto">
            {notifications.map((notification) => (
              <DropdownMenuItem
                key={notification.id}
                onSelect={() => open(notification)}
                className={cn('flex-col items-start gap-1 whitespace-normal', !notification.read_at && 'bg-accent/50')}
              >
                <span className="flex w-full items-start gap-2 text-sm">
                  {!notification.read_at && <span className="mt-1.5 size-2 shrink-0 rounded-full bg-primary" />}
                  <span dir="auto">{message(notification)}</span>
                </span>
                <span className="text-xs text-muted-foreground" dir="auto">
                  {notification.data.course_title} · {format.dateTime(notification.created_at)}
                </span>
              </DropdownMenuItem>
            ))}
          </div>
        )}
      </DropdownMenuContent>
    </DropdownMenu>
  )
}
