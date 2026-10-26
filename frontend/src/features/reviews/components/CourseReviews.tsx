import { EyeOff, Pencil, Trash2 } from 'lucide-react'
import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { Pagination } from '@/components/Pagination'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'
import { Skeleton } from '@/components/ui/skeleton'
import { Textarea } from '@/components/ui/textarea'
import { useDeleteReview, useMyReview, useReviews, useSaveReview } from '@/features/reviews/hooks'
import { useFormatters } from '@/hooks/useFormatters'
import { getFieldErrors, getGeneralError } from '@/lib/apiErrors'
import type { Course } from '@/types/course'
import type { Review } from '@/types/review'
import { StarInput, StarRating } from './StarRating'

/** Must match Review::MAX_LENGTH in the backend. */
const MAX_COMMENT_LENGTH = 1000

/**
 * The course page's reviews: the average with a star breakdown, the
 * enrolled student's own review (write, edit, delete), and the list.
 */
export function CourseReviews({ course }: { course: Course }) {
  const { t } = useTranslation()
  const format = useFormatters()
  const [page, setPage] = useState(1)
  const { data, isPending } = useReviews(course.slug, page)
  const summary = data?.meta.summary

  return (
    <section id="reviews" className="space-y-4">
      <h2 className="text-xl font-bold">{t('reviews.title')}</h2>

      {isPending ? (
        <Skeleton className="h-32" />
      ) : summary && summary.count > 0 ? (
        <div className="flex flex-wrap items-center gap-6 rounded-lg border p-5">
          <div className="text-center">
            <p className="text-4xl font-bold">{format.number(summary.average)}</p>
            <StarRating value={summary.average} className="text-lg" />
            <p className="text-xs text-muted-foreground">
              {t('reviews.count', { count: summary.count, value: format.number(summary.count) })}
            </p>
          </div>
          <div className="min-w-48 flex-1 space-y-1">
            {[5, 4, 3, 2, 1].map((stars) => {
              const count = summary.breakdown[String(stars)] ?? 0
              return (
                <div key={stars} className="flex items-center gap-2 text-xs">
                  <span className="w-14 shrink-0 text-muted-foreground">{t('reviews.starBar', { stars: format.number(stars) })}</span>
                  <Progress value={(count / summary.count) * 100} className="h-2" aria-label={t('reviews.starBar', { stars })} />
                  <span className="w-8 shrink-0 text-end text-muted-foreground">{format.number(count)}</span>
                </div>
              )
            })}
          </div>
        </div>
      ) : (
        <p className="text-sm text-muted-foreground">{t('reviews.noReviews')}</p>
      )}

      {course.enrollment && <MyReviewPanel slug={course.slug} />}

      {data && data.data.length > 0 && (
        <div className="divide-y rounded-lg border">
          {data.data.map((review) => (
            <ReviewItem key={review.id} review={review} teacherName={course.teacher?.name} />
          ))}
        </div>
      )}

      {data && <Pagination page={data.meta.current_page} lastPage={data.meta.last_page} onChange={setPage} />}
    </section>
  )
}

function ReviewItem({ review, teacherName }: { review: Review; teacherName?: string }) {
  const { t } = useTranslation()
  const format = useFormatters()

  return (
    <article className="space-y-2 p-4">
      <div className="flex items-center gap-3">
        <Avatar className="size-8">
          {review.student.avatar_url && <AvatarImage src={review.student.avatar_url} alt="" />}
          <AvatarFallback>{review.student.name.charAt(0)}</AvatarFallback>
        </Avatar>
        <div className="flex-1">
          <p className="text-sm font-medium">{review.student.name}</p>
          <div className="flex items-center gap-2 text-xs text-muted-foreground">
            <StarRating value={review.rating} />
            <time dateTime={review.created_at}>{format.date(review.created_at)}</time>
          </div>
        </div>
      </div>
      {review.comment && (
        <p dir="auto" className="text-sm whitespace-pre-line">
          {review.comment}
        </p>
      )}
      {review.teacher_reply && (
        <div className="ms-6 rounded-md bg-muted/60 p-3 text-sm">
          <p className="mb-1 text-xs font-semibold">
            {teacherName ? t('reviews.teacherReply', { name: teacherName }) : t('reviews.teacherReplyFallback')}
          </p>
          <p dir="auto" className="whitespace-pre-line">
            {review.teacher_reply}
          </p>
        </div>
      )}
    </article>
  )
}

/** The enrolled student's own review, or why they can't write one yet. */
function MyReviewPanel({ slug }: { slug: string }) {
  const { t } = useTranslation()
  const { data } = useMyReview(slug, true)
  const [editing, setEditing] = useState(false)
  const remove = useDeleteReview(slug)

  if (!data) return null

  const review = data.data

  if (!review && !data.meta.can_review) {
    return data.meta.reason ? <p className="text-sm text-muted-foreground">{data.meta.reason}</p> : null
  }

  if (!review || editing) {
    return (
      <Card>
        <CardContent className="space-y-3 p-5">
          <h3 className="font-semibold">{review ? t('reviews.edit') : t('reviews.write')}</h3>
          <ReviewForm slug={slug} review={review} onDone={() => setEditing(false)} onCancel={review ? () => setEditing(false) : undefined} />
        </CardContent>
      </Card>
    )
  }

  return (
    <Card>
      <CardContent className="space-y-3 p-5">
        <div className="flex flex-wrap items-center gap-3">
          <h3 className="me-auto font-semibold">{t('reviews.yours')}</h3>
          <Button size="sm" variant="ghost" onClick={() => setEditing(true)}>
            <Pencil />
            {t('reviews.edit')}
          </Button>
          <Button
            size="sm"
            variant="ghost"
            className="text-destructive hover:text-destructive"
            disabled={remove.isPending}
            onClick={() => {
              if (window.confirm(t('reviews.confirmDelete'))) remove.mutate()
            }}
          >
            <Trash2 />
            {t('reviews.delete')}
          </Button>
        </div>
        {review.is_hidden && (
          <Alert>
            <EyeOff />
            <AlertDescription>{t('reviews.hidden')}</AlertDescription>
          </Alert>
        )}
        <StarRating value={review.rating} className="text-lg" />
        {review.comment && (
          <p dir="auto" className="text-sm whitespace-pre-line">
            {review.comment}
          </p>
        )}
      </CardContent>
    </Card>
  )
}

function ReviewForm({
  slug,
  review,
  onDone,
  onCancel,
}: {
  slug: string
  review: Review | null
  onDone: () => void
  onCancel?: () => void
}) {
  const { t } = useTranslation()
  const save = useSaveReview(slug)
  const [rating, setRating] = useState(review?.rating ?? 0)
  const [comment, setComment] = useState(review?.comment ?? '')
  const errors = getFieldErrors(save.error)
  const error = errors.rating?.[0] ?? errors.comment?.[0] ?? getGeneralError(save.error, t('auth.errors.generic'))

  function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (rating < 1) return
    save.mutate({ rating, comment: comment.trim() }, { onSuccess: onDone })
  }

  return (
    <form onSubmit={submit} className="space-y-3">
      <StarInput value={rating} onChange={setRating} />
      <Textarea
        value={comment}
        onChange={(e) => setComment(e.target.value)}
        placeholder={t('reviews.commentPlaceholder')}
        maxLength={MAX_COMMENT_LENGTH}
        rows={3}
        dir="auto"
      />
      {error && <p className="text-sm text-destructive">{error}</p>}
      <div className="flex gap-2">
        <Button type="submit" size="sm" disabled={save.isPending || rating < 1}>
          {save.isPending ? t('reviews.saving') : review ? t('reviews.update') : t('reviews.submit')}
        </Button>
        {onCancel && (
          <Button type="button" size="sm" variant="ghost" onClick={onCancel}>
            {t('reviews.cancel')}
          </Button>
        )}
      </div>
    </form>
  )
}
