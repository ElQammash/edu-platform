import { Star } from 'lucide-react'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useFormatters } from '@/hooks/useFormatters'
import { cn } from '@/lib/utils'

const STARS = [1, 2, 3, 4, 5]

/** Read-only stars; a 4.5 average shows half of the fifth star. */
export function StarRating({ value, className }: { value: number; className?: string }) {
  const { t } = useTranslation()
  const format = useFormatters()
  const rounded = Math.round(value * 2) / 2

  return (
    <span
      role="img"
      aria-label={t('reviews.starsLabel', { value: format.number(rounded) })}
      // Stars fill left to right in both languages, like a gauge.
      dir="ltr"
      className={cn('inline-flex items-center gap-0.5 text-amber-500', className)}
    >
      {STARS.map((star) => {
        const fill = Math.min(1, Math.max(0, rounded - star + 1))
        return (
          <span key={star} className="relative inline-flex">
            <Star className="size-[1em] text-muted-foreground/40" />
            {fill > 0 && (
              <span className="absolute inset-0 overflow-hidden" style={{ width: `${fill * 100}%` }}>
                <Star className="size-[1em] fill-current" />
              </span>
            )}
          </span>
        )
      })}
    </span>
  )
}

/** Pick 1 to 5 stars; arrow keys work through the native radio group. */
export function StarInput({ value, onChange }: { value: number; onChange: (value: number) => void }) {
  const { t } = useTranslation()
  const format = useFormatters()
  const [hover, setHover] = useState(0)
  const shown = hover || value

  return (
    <div role="radiogroup" aria-label={t('reviews.ratingLabel')} dir="ltr" className="inline-flex gap-1" onMouseLeave={() => setHover(0)}>
      {STARS.map((star) => (
        <label key={star} className="cursor-pointer" onMouseEnter={() => setHover(star)}>
          <input
            type="radio"
            name="rating"
            value={star}
            checked={value === star}
            onChange={() => onChange(star)}
            className="peer sr-only"
            aria-label={t('reviews.starsLabel', { value: format.number(star) })}
          />
          <Star
            className={cn(
              'size-7 rounded-sm transition-colors peer-focus-visible:ring-2 peer-focus-visible:ring-ring',
              star <= shown ? 'fill-amber-500 text-amber-500' : 'text-muted-foreground/50',
            )}
          />
        </label>
      ))}
    </div>
  )
}
