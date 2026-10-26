import { useTranslation } from 'react-i18next'
import { Badge } from '@/components/ui/badge'
import { cn } from '@/lib/utils'
import type { PaymentState } from '@/types/payment'

const COLORS: Record<PaymentState, string> = {
  pending: 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200',
  paid: 'bg-green-600 text-white',
  partially_refunded: 'bg-sky-100 text-sky-900 dark:bg-sky-950 dark:text-sky-200',
  refunded: 'bg-red-100 text-red-900 dark:bg-red-950 dark:text-red-200',
  failed: 'bg-red-600 text-white',
  expired: 'bg-muted text-muted-foreground',
}

export function PaymentStatusBadge({ status }: { status: PaymentState }) {
  const { t } = useTranslation()

  return <Badge className={cn('border-transparent', COLORS[status])}>{t(`payments.status.${status}`)}</Badge>
}
