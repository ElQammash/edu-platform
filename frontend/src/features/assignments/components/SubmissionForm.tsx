import { CircleCheck, FileText, Paperclip, X } from 'lucide-react'
import { useRef, useState, type ChangeEvent, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Field, FieldDescription, FieldError, FieldGroup, FieldLabel } from '@/components/ui/field'
import { Textarea } from '@/components/ui/textarea'
import { useSubmitAssignment } from '@/features/assignments/hooks'
import { useFormatters } from '@/hooks/useFormatters'
import { getFieldErrors, getGeneralError } from '@/lib/apiErrors'
import type { Assignment, Submission } from '@/types/assignment'

/** Must match academy.submissions in the backend config. */
const MAX_FILE_MB = 10
const ACCEPT = 'application/pdf,image/jpeg,image/png,image/webp'

/**
 * Create or edit the student's single submission: text and/or one file.
 * Editing stays possible until the teacher grades it.
 */
export function SubmissionForm({ assignment }: { assignment: Assignment }) {
  const { t } = useTranslation()
  const format = useFormatters()
  const submit = useSubmitAssignment(assignment.id)
  const fileInput = useRef<HTMLInputElement>(null)

  const existing = assignment.submission ?? null
  const [content, setContent] = useState(existing?.content ?? '')
  const [file, setFile] = useState<File | null>(null)
  const [removeFile, setRemoveFile] = useState(false)
  const [fileError, setFileError] = useState<string | null>(null)

  const errors = getFieldErrors(submit.error)
  const generalError = getGeneralError(submit.error, t('auth.errors.generic'))
  const keptFile = !file && !removeFile ? existing?.file : null

  function pickFile(e: ChangeEvent<HTMLInputElement>) {
    const picked = e.target.files?.[0] ?? null
    if (picked && picked.size > MAX_FILE_MB * 1024 * 1024) {
      setFileError(t('assignments.fileTooLarge', { size: format.number(MAX_FILE_MB) }))
      e.target.value = ''
      return
    }
    setFileError(null)
    setFile(picked)
    if (picked) setRemoveFile(false)
  }

  function clearFile() {
    setFile(null)
    if (fileInput.current) fileInput.current.value = ''
    if (existing?.file) setRemoveFile(true)
  }

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    submit.mutate(
      { content, file, removeFile },
      {
        onSuccess: () => {
          setFile(null)
          setRemoveFile(false)
          if (fileInput.current) fileInput.current.value = ''
        },
      },
    )
  }

  return (
    <form onSubmit={handleSubmit} noValidate>
      <FieldGroup>
        {submit.isSuccess && !submit.isPending && (
          <Alert>
            <CircleCheck />
            <AlertDescription>{t('assignments.submittedOk')}</AlertDescription>
          </Alert>
        )}
        {generalError && (
          <Alert variant="destructive">
            <AlertDescription>{generalError}</AlertDescription>
          </Alert>
        )}
        {errors.submission && (
          <Alert variant="destructive">
            <AlertDescription>{errors.submission.join(' ')}</AlertDescription>
          </Alert>
        )}

        {existing && <SubmissionStatus submission={existing} />}

        <Field data-invalid={errors.content ? true : undefined}>
          <FieldLabel htmlFor="submission-content">{t('assignments.yourAnswer')}</FieldLabel>
          <Textarea
            id="submission-content"
            dir="auto"
            rows={8}
            value={content}
            onChange={(e) => setContent(e.target.value)}
            placeholder={t('assignments.answerPlaceholder')}
            aria-invalid={errors.content ? true : undefined}
          />
          {errors.content && <FieldError errors={errors.content.map((message) => ({ message }))} />}
        </Field>

        <Field data-invalid={errors.file || fileError ? true : undefined}>
          <FieldLabel>{t('assignments.file')}</FieldLabel>
          {file || keptFile ? (
            <div className="flex items-center gap-3 rounded-md border px-3 py-2 text-sm">
              <FileText className="size-4 shrink-0 text-muted-foreground" />
              {file ? (
                <span className="flex-1 truncate" dir="auto">
                  {file.name}
                </span>
              ) : (
                <a
                  href={keptFile!.url}
                  target="_blank"
                  // No "noreferrer": the API needs the Referer to recognise the signed-in session.
                  rel="noopener"
                  className="flex-1 truncate text-primary underline-offset-4 hover:underline"
                  dir="auto"
                >
                  {keptFile!.name}
                </a>
              )}
              <Button type="button" variant="ghost" size="icon-sm" onClick={clearFile} aria-label={t('assignments.removeFile')}>
                <X />
              </Button>
            </div>
          ) : null}
          <div>
            <Button type="button" variant="outline" size="sm" onClick={() => fileInput.current?.click()}>
              <Paperclip />
              {file || keptFile ? t('assignments.replaceFile') : t('assignments.attach')}
            </Button>
          </div>
          <input ref={fileInput} type="file" accept={ACCEPT} className="hidden" onChange={pickFile} />
          <FieldDescription>{t('assignments.fileHint', { size: format.number(MAX_FILE_MB) })}</FieldDescription>
          {fileError && <FieldError>{fileError}</FieldError>}
          {errors.file && <FieldError errors={errors.file.map((message) => ({ message }))} />}
        </Field>

        <div className="space-y-1">
          <Button type="submit" disabled={submit.isPending}>
            {submit.isPending ? t('assignments.submitting') : existing ? t('assignments.update') : t('assignments.submit')}
          </Button>
          <p className="text-xs text-muted-foreground">{t('assignments.editHint')}</p>
        </div>
      </FieldGroup>
    </form>
  )
}

export function SubmissionStatus({ submission }: { submission: Submission }) {
  const { t } = useTranslation()
  const format = useFormatters()

  return (
    <p className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
      <CircleCheck className="size-4 text-green-600" />
      {t('assignments.submitted', { date: format.dateTime(submission.submitted_at) })}
      {submission.is_late && <Badge variant="destructive">{t('assignments.late')}</Badge>}
      {!submission.is_graded && <span>· {t('assignments.waiting')}</span>}
    </p>
  )
}
