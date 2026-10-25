import { ChevronLeft, ChevronRight } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { Button } from '@/components/ui/button'
import { useFormatters } from '@/hooks/useFormatters'

/** Previous/next pager. Chevrons flip in RTL so "next" always points forward. */
export function Pagination({ page, lastPage, onChange }: { page: number; lastPage: number; onChange: (page: number) => void }) {
  const { t } = useTranslation()
  const format = useFormatters()

  if (lastPage <= 1) return null

  return (
    <nav className="flex items-center justify-center gap-4" aria-label={t('common.pageOf', { page, total: lastPage })}>
      <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => onChange(page - 1)}>
        <ChevronLeft className="rtl:rotate-180" />
        {t('common.previous')}
      </Button>
      <span className="text-sm text-muted-foreground">
        {t('common.pageOf', { page: format.number(page), total: format.number(lastPage) })}
      </span>
      <Button variant="outline" size="sm" disabled={page >= lastPage} onClick={() => onChange(page + 1)}>
        {t('common.next')}
        <ChevronRight className="rtl:rotate-180" />
      </Button>
    </nav>
  )
}
