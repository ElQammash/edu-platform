import { CreditCard, MessageCircle } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { Link, useLocation } from 'react-router'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { Progress } from '@/components/ui/progress'
import { useAuth } from '@/features/auth/hooks'
import { useEnroll } from '@/features/courses/hooks'
import { useCheckout } from '@/features/payments/hooks'
import { useFormatters } from '@/hooks/useFormatters'
import { useSettings, whatsAppLink } from '@/hooks/useSettings'
import { getGeneralError } from '@/lib/apiErrors'
import { paths } from '@/routes/paths'
import type { Course } from '@/types/course'
import { nextLessonToOpen } from '@/features/courses/utils'

/**
 * The call to action on the course page:
 * - enrolled: progress and "continue learning";
 * - free course: enroll in one click (guests log in first);
 * - paid course: pay online (card or mobile wallet), or WhatsApp the
 *   academy to pay cash and be enrolled by the admin.
 */
export function EnrollPanel({ course }: { course: Course }) {
  const { t } = useTranslation()
  const format = useFormatters()
  const location = useLocation()
  const { user, hasRole } = useAuth()
  const enroll = useEnroll(course.slug)
  const checkout = useCheckout(course.slug)
  const { data: settings } = useSettings()

  const nextLesson = nextLessonToOpen(course.sections)
  const enrollment = course.enrollment

  if (enrollment) {
    return (
      <div className="space-y-3">
        <Progress value={enrollment.progress_percent} />
        <p className="text-sm text-muted-foreground">
          {t('courses.enroll.progress', { value: format.number(enrollment.progress_percent) })}
        </p>
        {nextLesson && (
          <Button asChild className="w-full" size="lg">
            <Link to={paths.lesson(nextLesson.id)}>
              {enrollment.progress_percent > 0 ? t('courses.enroll.continue') : t('courses.enroll.start')}
            </Link>
          </Button>
        )}
      </div>
    )
  }

  // Teachers and admins open lessons from the curriculum; enrolling is for students.
  if (user && !hasRole('student')) {
    return <p className="text-sm text-muted-foreground">{t('courses.enroll.staffOnly')}</p>
  }

  if (course.is_free) {
    if (!user) {
      return (
        <Button asChild className="w-full" size="lg">
          <Link to={paths.login} state={{ from: location.pathname }}>
            {t('courses.enroll.loginToEnroll')}
          </Link>
        </Button>
      )
    }

    const error = getGeneralError(enroll.error, t('auth.errors.generic'))

    return (
      <div className="space-y-3">
        {error && (
          <Alert variant="destructive">
            <AlertDescription>{error}</AlertDescription>
          </Alert>
        )}
        <Button className="w-full" size="lg" disabled={enroll.isPending} onClick={() => enroll.mutate()}>
          {enroll.isPending ? t('courses.enroll.enrolling') : t('courses.enroll.free')}
        </Button>
      </div>
    )
  }

  const message = user
    ? t('courses.enroll.whatsappMessageWithEmail', { course: course.title, email: user.email })
    : t('courses.enroll.whatsappMessage', { course: course.title })
  const link = whatsAppLink(settings?.whatsapp_number, message)

  const checkoutError = getGeneralError(checkout.error, t('auth.errors.generic'))

  return (
    <div className="space-y-3">
      {checkoutError && (
        <Alert variant="destructive">
          <AlertDescription>{checkoutError}</AlertDescription>
        </Alert>
      )}
      {user ? (
        <Button className="w-full" size="lg" disabled={checkout.isPending || checkout.isSuccess} onClick={() => checkout.mutate()}>
          <CreditCard />
          {checkout.isPending || checkout.isSuccess
            ? t('payments.redirecting')
            : t('payments.pay', { amount: `${format.number(course.price)} ${t('payments.currency')}` })}
        </Button>
      ) : (
        <Button asChild className="w-full" size="lg">
          <Link to={paths.login} state={{ from: location.pathname }}>
            {t('payments.loginToPay')}
          </Link>
        </Button>
      )}
      <p className="text-xs text-muted-foreground">{t('payments.methodsHint')}</p>

      <div className="space-y-2 border-t pt-3">
        <p className="text-sm text-muted-foreground">{t('courses.enroll.paidHint')}</p>
        {link ? (
          <Button asChild variant="outline" className="w-full border-[#25D366] text-[#128C4B] hover:bg-[#25D366]/10" size="sm">
            <a href={link} target="_blank" rel="noopener noreferrer">
              <MessageCircle />
              {t('courses.enroll.contactWhatsApp')}
            </a>
          </Button>
        ) : (
          <p className="text-sm text-muted-foreground">{t('courses.enroll.noContact')}</p>
        )}
      </div>
    </div>
  )
}
