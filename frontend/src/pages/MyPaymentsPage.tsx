import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { ErrorState } from '@/components/ErrorState'
import { FullPageSpinner } from '@/components/FullPageSpinner'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { PaymentStatusBadge } from '@/features/payments/components/PaymentStatusBadge'
import { useMyPayments } from '@/features/payments/hooks'
import { useFormatters } from '@/hooks/useFormatters'
import { paths } from '@/routes/paths'

export default function MyPaymentsPage() {
  const { t } = useTranslation()
  const format = useFormatters()
  const { data, isPending, isError, refetch } = useMyPayments()

  if (isPending) return <FullPageSpinner />
  if (isError) return <ErrorState onRetry={() => void refetch()} />

  const money = (value: number) => `${format.number(value)} ${t('payments.currency')}`

  return (
    <div className="mx-auto max-w-4xl space-y-6 px-4 py-10">
      <header className="space-y-1">
        <h1 className="text-3xl font-bold">{t('pages.myPayments.title')}</h1>
        <p className="text-muted-foreground">{t('payments.history.subtitle')}</p>
      </header>

      {data.data.length === 0 ? (
        <div className="flex flex-col items-center gap-4 py-16 text-center">
          <p className="text-muted-foreground">{t('payments.history.empty')}</p>
          <Button asChild>
            <Link to={paths.catalog}>{t('myCourses.browse')}</Link>
          </Button>
        </div>
      ) : (
        <Card className="py-0">
          <CardContent className="divide-y p-0">
            {data.data.map((payment) => (
              <Link
                key={payment.reference}
                to={paths.payment(payment.reference)}
                className="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-4 transition-colors hover:bg-accent/50"
              >
                <div className="min-w-0 flex-1">
                  <p dir="auto" className="truncate font-medium">
                    {payment.course.title}
                  </p>
                  <p className="text-xs text-muted-foreground">
                    {format.dateTime(payment.created_at)}
                    {payment.payment_method && <span dir="ltr"> · {payment.payment_method}</span>}
                  </p>
                </div>
                <div className="text-end">
                  <p className="font-semibold">{money(payment.amount)}</p>
                  {payment.refunded_amount > 0 && (
                    <p className="text-xs text-destructive">{t('payments.history.refunded', { amount: money(payment.refunded_amount) })}</p>
                  )}
                </div>
                <PaymentStatusBadge status={payment.status} />
              </Link>
            ))}
          </CardContent>
        </Card>
      )}
    </div>
  )
}
