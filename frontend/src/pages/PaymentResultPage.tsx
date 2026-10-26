import axios from 'axios'
import { CircleCheck, CircleX, Clock, LoaderCircle } from 'lucide-react'
import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useParams } from 'react-router'
import { ErrorState } from '@/components/ErrorState'
import { FullPageSpinner } from '@/components/FullPageSpinner'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { CONFIRMATION_WAIT_MS, useCheckout, usePayment } from '@/features/payments/hooks'
import { useFormatters } from '@/hooks/useFormatters'
import { paths } from '@/routes/paths'

/**
 * Where the student lands after the payment page. Paymob's server
 * confirms the payment separately, so this page waits for our records to
 * say "paid" rather than trusting the redirect.
 */
export default function PaymentResultPage() {
  const { t } = useTranslation()
  const format = useFormatters()
  const { reference = '' } = useParams()
  const [startedAt] = useState(() => Date.now())
  const [timedOut, setTimedOut] = useState(false)
  const { data: payment, isPending, isError, error, refetch } = usePayment(reference, startedAt)
  const retry = useCheckout(payment?.course.slug ?? '')

  useEffect(() => {
    const timer = setTimeout(() => setTimedOut(true), CONFIRMATION_WAIT_MS)
    return () => clearTimeout(timer)
  }, [])

  if (isPending) return <FullPageSpinner />
  if (isError) {
    const status = axios.isAxiosError(error) ? error.response?.status : undefined
    return <ErrorState message={status === 403 || status === 404 ? t('payments.result.notFound') : undefined} onRetry={() => void refetch()} />
  }

  const stillWaiting = payment.status === 'pending' && !timedOut
  const amount = `${format.number(payment.amount)} ${t('payments.currency')}`

  return (
    <div className="mx-auto max-w-lg px-4 py-16">
      <Card>
        <CardContent className="flex flex-col items-center gap-4 p-8 text-center">
          {payment.status === 'paid' || payment.status === 'partially_refunded' ? (
            <>
              <CircleCheck className="size-14 text-green-600" />
              <h1 className="text-2xl font-bold">{t('payments.result.paidTitle')}</h1>
              <p className="text-muted-foreground">{t('payments.result.paidText', { amount, course: payment.course.title })}</p>
              <Button asChild size="lg">
                <Link to={paths.courseDetails(payment.course.slug)}>{t('payments.result.openCourse')}</Link>
              </Button>
            </>
          ) : payment.status === 'pending' ? (
            <>
              {stillWaiting ? <LoaderCircle className="size-14 animate-spin text-muted-foreground" /> : <Clock className="size-14 text-amber-500" />}
              <h1 className="text-2xl font-bold">{stillWaiting ? t('payments.result.waitingTitle') : t('payments.result.slowTitle')}</h1>
              <p className="text-muted-foreground">{stillWaiting ? t('payments.result.waitingText') : t('payments.result.slowText')}</p>
              {payment.checkout_url && !stillWaiting && (
                <Button asChild variant="outline">
                  <a href={payment.checkout_url}>{t('payments.result.backToCheckout')}</a>
                </Button>
              )}
            </>
          ) : (
            <>
              <CircleX className="size-14 text-destructive" />
              <h1 className="text-2xl font-bold">{t(`payments.result.${payment.status}Title`)}</h1>
              <p className="text-muted-foreground">{t(`payments.result.${payment.status}Text`)}</p>
              {(payment.status === 'failed' || payment.status === 'expired') && (
                <Button size="lg" disabled={retry.isPending} onClick={() => retry.mutate()}>
                  {retry.isPending ? t('payments.redirecting') : t('payments.result.tryAgain')}
                </Button>
              )}
            </>
          )}

          <p className="text-xs text-muted-foreground" dir="auto">
            {payment.course.title} · {amount}
            <br />
            <span dir="ltr">{payment.reference}</span>
          </p>
          <Link to={paths.myPayments} className="text-sm underline underline-offset-4">
            {t('pages.myPayments.title')}
          </Link>
        </CardContent>
      </Card>
    </div>
  )
}
