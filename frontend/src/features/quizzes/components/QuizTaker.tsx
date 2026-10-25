import { useQueryClient } from '@tanstack/react-query'
import { CircleCheck, CloudAlert, LoaderCircle, Timer } from 'lucide-react'
import { useCallback, useEffect, useRef, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Textarea } from '@/components/ui/textarea'
import { quizKeys, useSaveAnswers, useSubmitAttempt } from '@/features/quizzes/hooks'
import { formatClock, useCountdown } from '@/features/quizzes/useCountdown'
import { optionLabel } from '@/features/quizzes/utils'
import { useFormatters } from '@/hooks/useFormatters'
import { getGeneralError } from '@/lib/apiErrors'
import { cn } from '@/lib/utils'
import type { AnswerInput, Attempt, AttemptQuestion } from '@/types/quiz'

type Answers = Record<number, { option_id: number | null; text_answer: string | null }>

const AUTOSAVE_DELAY_MS = 1200

/**
 * Answering an open attempt: questions in the attempt's stored order, a
 * countdown by the server's clock, autosave shortly after each change, and
 * an automatic submit when time runs out (the server enforces the deadline
 * either way).
 */
export function QuizTaker({ attempt }: { attempt: Attempt }) {
  const { t } = useTranslation()
  const format = useFormatters()
  const queryClient = useQueryClient()
  const save = useSaveAnswers(attempt.id)
  const submit = useSubmitAttempt(attempt.id, attempt.quiz.id)

  const [answers, setAnswers] = useState<Answers>(() =>
    Object.fromEntries(attempt.questions.map((q) => [q.id, { option_id: q.answer.option_id, text_answer: q.answer.text_answer }])),
  )
  const [saveState, setSaveState] = useState<'saved' | 'saving' | 'error'>('saved')
  const [confirming, setConfirming] = useState(false)

  const dirty = useRef(new Set<number>())
  const timer = useRef<ReturnType<typeof setTimeout>>(undefined)
  // The answers to send: updated together with the state, never one render
  // behind, so a save always carries what the student just chose.
  const latest = useRef(answers)
  // Mutation objects change identity on every render; keep a stable handle.
  const saveRef = useRef(save.mutateAsync)
  useEffect(() => {
    saveRef.current = save.mutateAsync
  })

  const left = useCountdown(attempt.expires_at, attempt.server_time)

  /** Send every changed answer now. Stable; resolves even if saving fails. */
  const flush = useCallback(async () => {
    clearTimeout(timer.current)
    if (dirty.current.size === 0) return
    const ids = [...dirty.current]
    dirty.current.clear()
    const payload: AnswerInput[] = ids.map((id) => ({ question_id: id, ...latest.current[id] }))
    setSaveState('saving')
    try {
      await saveRef.current(payload)
      setSaveState(dirty.current.size ? 'saving' : 'saved')
    } catch {
      ids.forEach((id) => dirty.current.add(id))
      setSaveState('error')
    }
  }, [])

  function change(questionId: number, value: Partial<Answers[number]>) {
    const next = { ...latest.current, [questionId]: { ...latest.current[questionId], ...value } }
    latest.current = next
    setAnswers(next)
    dirty.current.add(questionId)
    setSaveState('saving')
    clearTimeout(timer.current)
    timer.current = setTimeout(() => void flush(), AUTOSAVE_DELAY_MS)
  }

  const submitRef = useRef(submit.mutate)
  useEffect(() => {
    submitRef.current = submit.mutate
  })

  const finish = useCallback(async () => {
    await flush()
    submitRef.current(undefined, {
      // Too late (the deadline passed meanwhile): the server already closed
      // the attempt; reload it to show the result.
      onError: () => void queryClient.invalidateQueries({ queryKey: quizKeys.attempt(attempt.id) }),
    })
  }, [flush, queryClient, attempt.id])

  // Time is up: submit what is there.
  const timeUp = left === 0
  const autoSubmitted = useRef(false)
  useEffect(() => {
    if (timeUp && !autoSubmitted.current) {
      autoSubmitted.current = true
      void finish()
    }
  }, [timeUp, finish])

  // Save before leaving the page or hiding the tab.
  useEffect(() => {
    const onHide = () => document.visibilityState === 'hidden' && void flush()
    document.addEventListener('visibilitychange', onHide)
    return () => {
      document.removeEventListener('visibilitychange', onHide)
      void flush()
    }
  }, [flush])

  const answeredCount = attempt.questions.filter((q) => isAnswered(q, answers[q.id])).length
  const unanswered = attempt.questions.length - answeredCount
  const submitError = getGeneralError(submit.error, t('auth.errors.generic'))

  return (
    <div className="space-y-6">
      <div className="sticky top-0 z-10 -mx-4 flex flex-wrap items-center gap-3 border-b bg-background/95 px-4 py-3 backdrop-blur">
        <h1 dir="auto" className="me-auto text-lg font-semibold">
          {attempt.quiz.title}
        </h1>
        <SaveIndicator state={saveState} />
        <span className="text-sm text-muted-foreground">
          {t('quizzes.answered', { answered: format.number(answeredCount), total: format.number(attempt.questions.length) })}
        </span>
        {left !== null && (
          <span
            className={cn(
              'flex items-center gap-1 rounded-md border px-2 py-1 font-mono text-sm tabular-nums',
              left < 60_000 && 'border-destructive text-destructive',
            )}
            role="timer"
            aria-label={t('quizzes.timeLeft')}
            dir="ltr"
          >
            <Timer className="size-4" />
            {formatClock(left)}
          </span>
        )}
      </div>

      {timeUp ? (
        <Alert variant="destructive">
          <AlertDescription>{t('quizzes.timeUp')}</AlertDescription>
        </Alert>
      ) : (
        left !== null &&
        left < 60_000 && (
          <Alert variant="destructive">
            <AlertDescription>{t('quizzes.lowTime')}</AlertDescription>
          </Alert>
        )
      )}

      {attempt.questions.map((question, index) => (
        <QuestionCard
          key={question.id}
          number={index + 1}
          question={question}
          answer={answers[question.id]}
          disabled={timeUp || submit.isPending}
          onChange={(value) => change(question.id, value)}
        />
      ))}

      {submitError && (
        <Alert variant="destructive">
          <AlertDescription>{submitError}</AlertDescription>
        </Alert>
      )}

      {confirming ? (
        <Card>
          <CardContent className="space-y-3 pt-6">
            <p className="font-medium">{t('quizzes.confirmSubmit')}</p>
            {unanswered > 0 && <p className="text-sm text-destructive">{t('quizzes.unanswered', { count: unanswered, value: format.number(unanswered) })}</p>}
            <div className="flex gap-2">
              <Button disabled={submit.isPending || timeUp} onClick={() => void finish()}>
                {submit.isPending ? t('quizzes.submitting') : t('quizzes.submit')}
              </Button>
              <Button variant="outline" onClick={() => setConfirming(false)}>
                {t('common.cancel')}
              </Button>
            </div>
          </CardContent>
        </Card>
      ) : (
        <Button size="lg" disabled={timeUp || submit.isPending} onClick={() => setConfirming(true)}>
          {t('quizzes.submit')}
        </Button>
      )}
    </div>
  )
}

function isAnswered(question: AttemptQuestion, answer: Answers[number] | undefined): boolean {
  return question.type === 'essay' ? Boolean(answer?.text_answer?.trim()) : answer?.option_id != null
}

function SaveIndicator({ state }: { state: 'saved' | 'saving' | 'error' }) {
  const { t } = useTranslation()

  if (state === 'saving') {
    return (
      <span className="flex items-center gap-1 text-xs text-muted-foreground">
        <LoaderCircle className="size-3 animate-spin" />
        {t('quizzes.saving')}
      </span>
    )
  }

  if (state === 'error') {
    return (
      <span className="flex items-center gap-1 text-xs text-destructive">
        <CloudAlert className="size-3" />
        {t('quizzes.saveFailed')}
      </span>
    )
  }

  return (
    <span className="flex items-center gap-1 text-xs text-muted-foreground">
      <CircleCheck className="size-3 text-green-600" />
      {t('quizzes.saved')}
    </span>
  )
}

function QuestionCard({
  number,
  question,
  answer,
  disabled,
  onChange,
}: {
  number: number
  question: AttemptQuestion
  answer: Answers[number] | undefined
  disabled: boolean
  onChange: (value: Partial<Answers[number]>) => void
}) {
  const { t } = useTranslation()
  const format = useFormatters()
  const name = `question-${question.id}`

  return (
    <Card>
      <CardHeader className="flex-row items-start justify-between gap-4">
        <CardTitle className="flex gap-2 text-base leading-7 font-medium">
          <span className="text-muted-foreground">{format.number(number)}.</span>
          <span dir="auto" className="whitespace-pre-line">
            {question.body}
          </span>
        </CardTitle>
        <span className="shrink-0 text-xs text-muted-foreground">{t('quizzes.points', { count: question.points, value: format.number(question.points) })}</span>
      </CardHeader>
      <CardContent>
        {question.type === 'essay' ? (
          <Textarea
            dir="auto"
            rows={6}
            value={answer?.text_answer ?? ''}
            disabled={disabled}
            placeholder={t('quizzes.essayPlaceholder')}
            aria-label={question.body}
            onChange={(e) => onChange({ text_answer: e.target.value })}
          />
        ) : (
          <fieldset className="space-y-2" disabled={disabled}>
            <legend className="sr-only">{question.body}</legend>
            {question.options.map((option) => (
              <label
                key={option.id}
                className={cn(
                  'flex cursor-pointer items-center gap-3 rounded-md border px-3 py-2 transition-colors hover:bg-accent/60',
                  answer?.option_id === option.id && 'border-primary bg-primary/5',
                )}
              >
                <input
                  type="radio"
                  name={name}
                  className="size-4 accent-primary"
                  checked={answer?.option_id === option.id}
                  onChange={() => onChange({ option_id: option.id })}
                />
                <span dir="auto">{optionLabel(question, option.body, t)}</span>
              </label>
            ))}
          </fieldset>
        )}
      </CardContent>
    </Card>
  )
}
