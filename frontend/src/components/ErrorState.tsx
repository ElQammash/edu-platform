import { TriangleAlert } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { Button } from '@/components/ui/button'

export function ErrorState({ message, onRetry }: { message?: string; onRetry?: () => void }) {
  const { t } = useTranslation()

  return (
    <div className="flex flex-col items-center gap-3 py-16 text-center" role="alert">
      <TriangleAlert className="size-8 text-muted-foreground" />
      <p className="text-muted-foreground">{message ?? t('common.loadFailed')}</p>
      {onRetry && (
        <Button variant="outline" onClick={onRetry}>
          {t('common.retry')}
        </Button>
      )}
    </div>
  )
}
