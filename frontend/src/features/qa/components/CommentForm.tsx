import type { UseMutationResult } from '@tanstack/react-query'
import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import { getFieldErrors, getGeneralError } from '@/lib/apiErrors'

/** Must match LessonComment::MAX_LENGTH in the backend. */
export const MAX_COMMENT_LENGTH = 2000

/** A question, reply or edit box. Clears itself after a new post is sent. */
export function CommentForm({
  mutation,
  initial = '',
  placeholder,
  submitLabel,
  onDone,
  onCancel,
  autoFocus = false,
}: {
  mutation: UseMutationResult<unknown, Error, string>
  initial?: string
  placeholder?: string
  submitLabel: string
  onDone?: () => void
  onCancel?: () => void
  autoFocus?: boolean
}) {
  const { t } = useTranslation()
  const [body, setBody] = useState(initial)
  const error = getFieldErrors(mutation.error).body?.[0] ?? getGeneralError(mutation.error, t('auth.errors.generic'))

  function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const text = body.trim()
    if (text.length < 2) return

    mutation.mutate(text, {
      onSuccess: () => {
        if (!initial) setBody('')
        onDone?.()
      },
    })
  }

  return (
    <form onSubmit={submit} className="space-y-2">
      <Textarea
        value={body}
        onChange={(e) => setBody(e.target.value)}
        placeholder={placeholder}
        maxLength={MAX_COMMENT_LENGTH}
        rows={3}
        dir="auto"
        autoFocus={autoFocus}
        aria-invalid={Boolean(error)}
      />
      {error && <p className="text-sm text-destructive">{error}</p>}
      <div className="flex items-center gap-2">
        <Button type="submit" size="sm" disabled={mutation.isPending || body.trim().length < 2}>
          {mutation.isPending ? t('qa.sending') : submitLabel}
        </Button>
        {onCancel && (
          <Button type="button" size="sm" variant="ghost" onClick={onCancel}>
            {t('qa.cancel')}
          </Button>
        )}
        {body.length > MAX_COMMENT_LENGTH * 0.8 && (
          <span className="ms-auto text-xs text-muted-foreground" dir="ltr">
            {body.length} / {MAX_COMMENT_LENGTH}
          </span>
        )}
      </div>
    </form>
  )
}
