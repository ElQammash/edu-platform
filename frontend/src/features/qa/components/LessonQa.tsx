import axios from 'axios'
import { CircleCheck, MessageCircleQuestion, Pencil, RotateCcw, Trash2 } from 'lucide-react'
import { useEffect, useState, type ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { useLocation } from 'react-router'
import { Pagination } from '@/components/Pagination'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import {
  useAcceptReply,
  useAskQuestion,
  useDeleteComment,
  useEditComment,
  useLessonComments,
  useReply,
  useSetResolved,
} from '@/features/qa/hooks'
import { useFormatters } from '@/hooks/useFormatters'
import { cn } from '@/lib/utils'
import type { LessonComment, QaFilter } from '@/types/qa'
import { CommentForm } from './CommentForm'

const FILTERS: QaFilter[] = ['all', 'unresolved', 'mine']

/**
 * The lesson's Q&A for everyone in the course: students ask, anyone in the
 * course replies, the asker or the teacher accepts an answer. Hidden for
 * visitors who cannot take part (free-preview guests, other students).
 */
export function LessonQa({ lessonId }: { lessonId: number }) {
  const { t } = useTranslation()
  const format = useFormatters()
  const { hash } = useLocation()
  const [filter, setFilter] = useState<QaFilter>('all')
  const [page, setPage] = useState(1)
  const { data, isPending, isError, error } = useLessonComments(lessonId, filter, page)
  const ask = useAskQuestion(lessonId)

  // Notifications link to #question-{id}: scroll there once it is rendered.
  useEffect(() => {
    if (!data || !hash.startsWith('#question-')) return
    document.getElementById(hash.slice(1))?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }, [data, hash])

  if (isError && axios.isAxiosError(error) && [401, 403].includes(error.response?.status ?? 0)) return null

  return (
    <Card id="qa">
      <CardHeader className="flex-row flex-wrap items-center gap-3">
        <CardTitle className="me-auto flex items-center gap-2 text-lg">
          <MessageCircleQuestion className="size-5" />
          {t('qa.title')}
          {data && data.meta.total > 0 && <Badge variant="secondary">{format.number(data.meta.total)}</Badge>}
        </CardTitle>
        <div className="flex gap-1" role="tablist">
          {FILTERS.map((value) => (
            <Button
              key={value}
              size="sm"
              role="tab"
              aria-selected={filter === value}
              variant={filter === value ? 'secondary' : 'ghost'}
              onClick={() => {
                setFilter(value)
                setPage(1)
              }}
            >
              {t(`qa.filters.${value}`)}
            </Button>
          ))}
        </div>
      </CardHeader>
      <CardContent className="space-y-6">
        {data?.meta.can_ask && (
          <CommentForm mutation={ask} placeholder={t('qa.askPlaceholder')} submitLabel={t('qa.ask')} />
        )}

        {isPending ? (
          <div className="space-y-3">
            <Skeleton className="h-20" />
            <Skeleton className="h-20" />
          </div>
        ) : isError ? (
          <p className="text-sm text-destructive">{t('qa.loadError')}</p>
        ) : data.data.length === 0 ? (
          <p className="py-6 text-center text-sm text-muted-foreground">{t(`qa.empty.${filter}`)}</p>
        ) : (
          <div className="divide-y">
            {data.data.map((question) => (
              <QuestionThread key={question.id} question={question} lessonId={lessonId} />
            ))}
          </div>
        )}

        {data && <Pagination page={data.meta.current_page} lastPage={data.meta.last_page} onChange={setPage} />}
      </CardContent>
    </Card>
  )
}

function QuestionThread({ question, lessonId }: { question: LessonComment; lessonId: number }) {
  const { t } = useTranslation()
  const [replying, setReplying] = useState(false)
  const reply = useReply(lessonId, question.id)
  const resolve = useSetResolved(lessonId, question.id)
  const accept = useAcceptReply(lessonId)
  const resolved = Boolean(question.resolved_at)
  const replies = question.replies ?? []

  return (
    <article id={`question-${question.id}`} className="scroll-mt-24 space-y-3 py-5 first:pt-0 last:pb-0">
      <Post comment={question} lessonId={lessonId}>
        {resolved && (
          <Badge className="gap-1 bg-green-600 text-white">
            <CircleCheck className="size-3" />
            {t('qa.resolved')}
          </Badge>
        )}
      </Post>

      {replies.length > 0 && (
        <div className="ms-6 space-y-3 border-s ps-4 sm:ms-10">
          {replies.map((item) => {
            const isAccepted = item.id === question.accepted_reply_id
            return (
              <div key={item.id} className={cn('rounded-lg', isAccepted && 'bg-green-50 p-3 ring-1 ring-green-600/30 dark:bg-green-950/30')}>
                <Post comment={item} lessonId={lessonId}>
                  {isAccepted && (
                    <Badge variant="outline" className="gap-1 border-green-600 text-green-700 dark:text-green-400">
                      <CircleCheck className="size-3" />
                      {t('qa.accepted')}
                    </Badge>
                  )}
                </Post>
                {item.can.accept && !isAccepted && (
                  <Button
                    size="sm"
                    variant="ghost"
                    className="mt-1 h-7 text-xs"
                    disabled={accept.isPending}
                    onClick={() => accept.mutate(item.id)}
                  >
                    <CircleCheck />
                    {t('qa.accept')}
                  </Button>
                )}
              </div>
            )
          })}
        </div>
      )}

      <div className="ms-6 space-y-2 sm:ms-10">
        {replying ? (
          <CommentForm
            mutation={reply}
            placeholder={t('qa.replyPlaceholder')}
            submitLabel={t('qa.reply')}
            onDone={() => setReplying(false)}
            onCancel={() => setReplying(false)}
            autoFocus
          />
        ) : (
          <div className="flex flex-wrap gap-1">
            {!question.is_deleted && (
              <Button size="sm" variant="outline" className="h-7 text-xs" onClick={() => setReplying(true)}>
                {t('qa.reply')}
              </Button>
            )}
            {question.can.resolve && (
              <Button
                size="sm"
                variant="ghost"
                className="h-7 text-xs"
                disabled={resolve.isPending}
                onClick={() => resolve.mutate(!resolved)}
              >
                {resolved ? <RotateCcw /> : <CircleCheck />}
                {resolved ? t('qa.reopen') : t('qa.markResolved')}
              </Button>
            )}
          </div>
        )}
      </div>
    </article>
  )
}

/** One post: author, time, body, and the author's edit/delete buttons. */
function Post({ comment, lessonId, children }: { comment: LessonComment; lessonId: number; children?: ReactNode }) {
  const { t } = useTranslation()
  const format = useFormatters()
  const [editing, setEditing] = useState(false)
  const edit = useEditComment(lessonId, comment.id)
  const remove = useDeleteComment(lessonId)

  if (comment.is_deleted || !comment.author) {
    return <p className="text-sm text-muted-foreground italic">{t('qa.deleted')}</p>
  }

  return (
    <div className="flex gap-3">
      <Avatar className="size-8">
        {comment.author.avatar_url && <AvatarImage src={comment.author.avatar_url} alt="" />}
        <AvatarFallback>{comment.author.name.charAt(0)}</AvatarFallback>
      </Avatar>
      <div className="min-w-0 flex-1 space-y-1">
        <div className="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
          <span className="font-medium">{comment.author.name}</span>
          {comment.by_staff && <Badge>{t('qa.teacher')}</Badge>}
          <time className="text-xs text-muted-foreground" dateTime={comment.created_at} title={format.dateTime(comment.created_at)}>
            {format.relative(comment.created_at)}
          </time>
          {comment.edited_at && <span className="text-xs text-muted-foreground">({t('qa.edited')})</span>}
          {children}
        </div>

        {editing ? (
          <CommentForm
            mutation={edit}
            initial={comment.body ?? ''}
            submitLabel={t('qa.save')}
            onDone={() => setEditing(false)}
            onCancel={() => setEditing(false)}
            autoFocus
          />
        ) : (
          <p dir="auto" className="text-sm whitespace-pre-line">
            {comment.body}
          </p>
        )}

        {!editing && (comment.can.edit || comment.can.delete) && (
          <div className="flex gap-1">
            {comment.can.edit && (
              <Button size="sm" variant="ghost" className="h-7 px-2 text-xs" onClick={() => setEditing(true)}>
                <Pencil />
                {t('qa.edit')}
              </Button>
            )}
            {comment.can.delete && (
              <Button
                size="sm"
                variant="ghost"
                className="h-7 px-2 text-xs text-destructive hover:text-destructive"
                disabled={remove.isPending}
                onClick={() => {
                  if (window.confirm(t('qa.confirmDelete'))) remove.mutate(comment.id)
                }}
              >
                <Trash2 />
                {t('qa.delete')}
              </Button>
            )}
          </div>
        )}
      </div>
    </div>
  )
}
