import { useState, type ChangeEvent, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useNavigate } from 'react-router'
import { FormTextField } from '@/components/FormTextField'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { FieldGroup } from '@/components/ui/field'
import { AuthCard } from '@/features/auth/components/AuthCard'
import { useRegister } from '@/features/auth/hooks'
import { getFieldErrors, getGeneralError } from '@/lib/apiErrors'
import { paths } from '@/routes/paths'

export default function RegisterPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const register = useRegister()

  const [form, setForm] = useState({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    grade_level: '',
    parent_phone: '',
  })
  const errors = getFieldErrors(register.error)
  const generalError = getGeneralError(register.error, t('auth.errors.generic'))

  const set = (field: keyof typeof form) => (e: ChangeEvent<HTMLInputElement>) =>
    setForm({ ...form, [field]: e.target.value })

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    register.mutate(
      {
        ...form,
        // Optional fields: send nothing rather than an empty string.
        grade_level: form.grade_level || undefined,
        parent_phone: form.parent_phone || undefined,
      },
      { onSuccess: () => navigate(paths.home, { replace: true }) },
    )
  }

  return (
    <AuthCard
      title={t('pages.register.title')}
      description={t('auth.register.description')}
      footer={
        <span>
          {t('auth.register.haveAccount')}{' '}
          <Link to={paths.login} className="font-medium text-foreground underline-offset-4 hover:underline">
            {t('auth.register.loginLink')}
          </Link>
        </span>
      }
    >
      <form onSubmit={handleSubmit} noValidate>
        <FieldGroup>
          {generalError && (
            <Alert variant="destructive">
              <AlertDescription>{generalError}</AlertDescription>
            </Alert>
          )}

          <FormTextField
            name="name"
            autoComplete="name"
            required
            label={t('auth.fields.name')}
            value={form.name}
            onChange={set('name')}
            errors={errors.name}
          />
          <FormTextField
            name="email"
            type="email"
            dir="ltr"
            autoComplete="email"
            required
            label={t('auth.fields.email')}
            value={form.email}
            onChange={set('email')}
            errors={errors.email}
          />
          <FormTextField
            name="password"
            type="password"
            dir="ltr"
            autoComplete="new-password"
            required
            label={t('auth.fields.password')}
            value={form.password}
            onChange={set('password')}
            errors={errors.password}
          />
          <FormTextField
            name="password_confirmation"
            type="password"
            dir="ltr"
            autoComplete="new-password"
            required
            label={t('auth.fields.passwordConfirmation')}
            value={form.password_confirmation}
            onChange={set('password_confirmation')}
            errors={errors.password_confirmation}
          />
          <FormTextField
            name="grade_level"
            label={t('auth.fields.gradeLevel')}
            description={t('auth.fields.optional')}
            value={form.grade_level}
            onChange={set('grade_level')}
            errors={errors.grade_level}
          />
          <FormTextField
            name="parent_phone"
            type="tel"
            dir="ltr"
            inputMode="numeric"
            placeholder="01012345678"
            label={t('auth.fields.parentPhone')}
            description={t('auth.fields.optional')}
            value={form.parent_phone}
            onChange={set('parent_phone')}
            errors={errors.parent_phone}
          />

          <Button type="submit" disabled={register.isPending} className="w-full">
            {register.isPending ? t('auth.register.submitting') : t('auth.register.submit')}
          </Button>
        </FieldGroup>
      </form>
    </AuthCard>
  )
}
