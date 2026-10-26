import { Search } from 'lucide-react'
import { useEffect, useRef, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useSearchParams } from 'react-router'
import { ErrorState } from '@/components/ErrorState'
import { Pagination } from '@/components/Pagination'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { CourseCard, CourseCardSkeleton, CourseGrid } from '@/features/courses/components/CourseCard'
import { useCourses } from '@/features/courses/hooks'
import { useFormatters } from '@/hooks/useFormatters'
import type { CourseFilters } from '@/types/course'

// Radix Select cannot use an empty value, so "no filter" is "all".
const ALL = 'all'

/** Filters live in the URL, so a filtered catalog can be shared or bookmarked. */
function filtersFrom(params: URLSearchParams): CourseFilters {
  const pick = <T extends string>(key: string, allowed: readonly T[]) => {
    const value = params.get(key) as T | null
    return value && allowed.includes(value) ? value : undefined
  }

  return {
    search: params.get('search') || undefined,
    language: pick('language', ['ar', 'en'] as const),
    type: pick('type', ['recorded', 'live', 'hybrid'] as const),
    price: pick('price', ['free', 'paid'] as const),
    sort: pick('sort', ['rating'] as const),
    page: Number(params.get('page')) > 1 ? Number(params.get('page')) : undefined,
  }
}

export default function CatalogPage() {
  const { t } = useTranslation()
  const format = useFormatters()
  const [params, setParams] = useSearchParams()
  const filters = filtersFrom(params)

  const [search, setSearch] = useState(filters.search ?? '')
  const searchTimer = useRef<ReturnType<typeof setTimeout>>(undefined)

  const { data, isPending, isError, refetch, isPlaceholderData } = useCourses(filters)

  useEffect(() => () => clearTimeout(searchTimer.current), [])

  // Typing updates the box at once and the URL (and so the query) after a pause.
  function changeSearch(value: string) {
    setSearch(value)
    clearTimeout(searchTimer.current)
    searchTimer.current = setTimeout(() => update({ search: value.trim() || undefined }), 400)
  }

  function update(changes: Partial<Record<keyof CourseFilters, string | undefined>>) {
    setParams((current) => {
      const next = new URLSearchParams(current)
      for (const [key, value] of Object.entries(changes)) {
        if (!value || value === ALL) next.delete(key)
        else next.set(key, value)
      }
      // Any filter change starts again from the first page.
      if (!('page' in changes)) next.delete('page')
      return next
    })
  }

  const hasFilters = Boolean(filters.search || filters.language || filters.type || filters.price)

  return (
    <div className="mx-auto max-w-6xl space-y-6 px-4 py-10">
      <header className="space-y-1">
        <h1 className="text-3xl font-bold">{t('pages.catalog.title')}</h1>
        <p className="text-muted-foreground">{t('courses.catalog.subtitle')}</p>
      </header>

      <div className="flex flex-wrap items-center gap-3">
        <div className="relative min-w-60 flex-1">
          <Search className="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            type="search"
            value={search}
            onChange={(e) => changeSearch(e.target.value)}
            placeholder={t('courses.catalog.searchPlaceholder')}
            aria-label={t('courses.catalog.searchPlaceholder')}
            className="ps-9"
          />
        </div>

        <Select value={filters.language ?? ALL} onValueChange={(value) => update({ language: value })}>
          <SelectTrigger className="w-40" aria-label={t('courses.catalog.allLanguages')}>
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value={ALL}>{t('courses.catalog.allLanguages')}</SelectItem>
            <SelectItem value="ar">{t('courses.language.ar')}</SelectItem>
            <SelectItem value="en">{t('courses.language.en')}</SelectItem>
          </SelectContent>
        </Select>

        <Select value={filters.type ?? ALL} onValueChange={(value) => update({ type: value })}>
          <SelectTrigger className="w-40" aria-label={t('courses.catalog.allTypes')}>
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value={ALL}>{t('courses.catalog.allTypes')}</SelectItem>
            <SelectItem value="recorded">{t('courses.type.recorded')}</SelectItem>
            <SelectItem value="live">{t('courses.type.live')}</SelectItem>
            <SelectItem value="hybrid">{t('courses.type.hybrid')}</SelectItem>
          </SelectContent>
        </Select>

        <Select value={filters.price ?? ALL} onValueChange={(value) => update({ price: value })}>
          <SelectTrigger className="w-36" aria-label={t('courses.catalog.allPrices')}>
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value={ALL}>{t('courses.catalog.allPrices')}</SelectItem>
            <SelectItem value="free">{t('courses.catalog.freeOnly')}</SelectItem>
            <SelectItem value="paid">{t('courses.catalog.paidOnly')}</SelectItem>
          </SelectContent>
        </Select>

        <Select value={filters.sort ?? 'newest'} onValueChange={(value) => update({ sort: value === 'newest' ? undefined : value })}>
          <SelectTrigger className="w-40" aria-label={t('reviews.sortLabel')}>
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="newest">{t('courses.catalog.sort.newest')}</SelectItem>
            <SelectItem value="rating">{t('courses.catalog.sort.rating')}</SelectItem>
          </SelectContent>
        </Select>

        {hasFilters && (
          <Button
            variant="ghost"
            onClick={() => {
              clearTimeout(searchTimer.current)
              setSearch('')
              setParams(new URLSearchParams())
            }}
          >
            {t('courses.catalog.clearFilters')}
          </Button>
        )}
      </div>

      {isError ? (
        <ErrorState onRetry={() => void refetch()} />
      ) : isPending ? (
        <CourseGrid>
          {Array.from({ length: 8 }, (_, i) => (
            <CourseCardSkeleton key={i} />
          ))}
        </CourseGrid>
      ) : data.data.length === 0 ? (
        <p className="py-16 text-center text-muted-foreground">{t('courses.catalog.empty')}</p>
      ) : (
        <>
          <p className="text-sm text-muted-foreground">
            {t('courses.catalog.results', { count: data.meta.total, value: format.number(data.meta.total) })}
          </p>
          <div className={isPlaceholderData ? 'opacity-60 transition-opacity' : undefined}>
            <CourseGrid>
              {data.data.map((course) => (
                <CourseCard key={course.id} course={course} />
              ))}
            </CourseGrid>
          </div>
          <Pagination
            page={data.meta.current_page}
            lastPage={data.meta.last_page}
            onChange={(page) => {
              update({ page: String(page) })
              window.scrollTo({ top: 0, behavior: 'smooth' })
            }}
          />
        </>
      )}
    </div>
  )
}
