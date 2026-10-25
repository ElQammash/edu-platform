import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useLocation, useNavigate } from 'react-router'
import { FormTextField } from '@/components/FormTextField'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { FieldGroup } from '@/components/ui/field'
import { AuthCard } from '@/features/auth/components/AuthCard'
import { useLogin } from '@/features/auth/hooks'
import { panelUrlFor } from '@/features/auth/redirects'
import { getFieldErrors, getGeneralError } from '@/lib/apiErrors'
import { paths } from '@/routes/paths'

export default function LoginPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const location = useLocation()
  const login = useLogin()

  const [form, setForm] = useState({ email: '', password: '', remember: false })
  const errors = getFieldErrors(login.error)
  const generalError = getGeneralError(login.error, t('auth.errors.generic'))

  // Where a protected route sent the user from, if anywhere.
  const from = (location.state as { from?: string } | null)?.from ?? paths.home

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    login.mutate(form, {
      onSuccess: (user) => {
        const panelUrl = panelUrlFor(user)
        if (panelUrl) {
          window.location.assign(panelUrl)
          return
        }
        navigate(from, { replace: true })
      },
    })
  }

  return (
    <AuthCard
      title={t('pages.login.title')}
      description={t('auth.login.description')}
      footer={
        <span>
          {t('auth.login.noAccount')}{' '}
          <Link to={paths.register} className="font-medium text-foreground underline-offset-4 hover:underline">
            {t('auth.login.registerLink')}
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
            name="email"
            type="email"
            dir="ltr"
            autoComplete="email"
            required
            label={t('auth.fields.email')}
            value={form.email}
            onChange={(e) => setForm({ ...form, email: e.target.value })}
            errors={errors.email}
          />
          <FormTextField
            name="password"
            type="password"
            dir="ltr"
            autoComplete="current-password"
            required
            label={t('auth.fields.password')}
            value={form.password}
            onChange={(e) => setForm({ ...form, password: e.target.value })}
            errors={errors.password}
          />

          <label className="flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              className="size-4 accent-primary"
              checked={form.remember}
              onChange={(e) => setForm({ ...form, remember: e.target.checked })}
            />
            {t('auth.fields.remember')}
          </label>

          <Button type="submit" disabled={login.isPending} className="w-full">
            {login.isPending ? t('auth.login.submitting') : t('auth.login.submit')}
          </Button>
        </FieldGroup>
      </form>
    </AuthCard>
  )
}
