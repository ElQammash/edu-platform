import { CircleCheck } from 'lucide-react'
import { useEffect, useRef, useState, type ChangeEvent, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { FormTextField } from '@/components/FormTextField'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { FieldError, FieldGroup } from '@/components/ui/field'
import { useAuth } from '@/features/auth/hooks'
import { useUpdateProfile } from '@/features/profile/hooks'
import { getFieldErrors, getGeneralError } from '@/lib/apiErrors'
import type { User } from '@/types/user'

export default function ProfilePage() {
  const { user } = useAuth()

  // RequireAuth guarantees a user; remount the form if the account changes.
  return user ? <ProfileForm key={user.id} user={user} /> : null
}

function ProfileForm({ user }: { user: User }) {
  const { t } = useTranslation()
  const update = useUpdateProfile()
  const fileInput = useRef<HTMLInputElement>(null)

  const [form, setForm] = useState({
    name: user.name,
    grade_level: user.student_profile?.grade_level ?? '',
    parent_phone: user.student_profile?.parent_phone ?? '',
  })
  const [avatar, setAvatar] = useState<File | null>(null)
  const [removeAvatar, setRemoveAvatar] = useState(false)
  const [preview, setPreview] = useState<string | null>(null)
  const previewUrl = useRef<string | null>(null)

  // Object URL for the picked file; the previous one is released.
  function choose(file: File | null) {
    if (previewUrl.current) URL.revokeObjectURL(previewUrl.current)
    previewUrl.current = file ? URL.createObjectURL(file) : null
    setAvatar(file)
    setPreview(previewUrl.current)
    if (!file && fileInput.current) fileInput.current.value = ''
  }

  useEffect(
    () => () => {
      if (previewUrl.current) URL.revokeObjectURL(previewUrl.current)
    },
    [],
  )

  const errors = getFieldErrors(update.error)
  const generalError = getGeneralError(update.error, t('auth.errors.generic'))
  const currentAvatar = removeAvatar ? null : (preview ?? user.student_profile?.avatar_url ?? null)

  const set = (field: keyof typeof form) => (e: ChangeEvent<HTMLInputElement>) => setForm({ ...form, [field]: e.target.value })

  function pickAvatar(e: ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0] ?? null
    choose(file)
    if (file) setRemoveAvatar(false)
  }

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    update.mutate(
      { ...form, avatar, remove_avatar: removeAvatar },
      {
        onSuccess: () => {
          choose(null)
          setRemoveAvatar(false)
        },
      },
    )
  }

  return (
    <div className="mx-auto w-full max-w-xl px-4 py-10">
      <Card>
        <CardHeader>
          <CardTitle className="text-xl">{t('pages.profile.title')}</CardTitle>
          <CardDescription>{t('profile.subtitle')}</CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit} noValidate>
            <FieldGroup>
              {update.isSuccess && !update.isPending && (
                <Alert>
                  <CircleCheck />
                  <AlertDescription>{t('profile.saved')}</AlertDescription>
                </Alert>
              )}
              {generalError && (
                <Alert variant="destructive">
                  <AlertDescription>{generalError}</AlertDescription>
                </Alert>
              )}

              <div className="flex items-center gap-4">
                <Avatar className="size-20">
                  {currentAvatar && <AvatarImage src={currentAvatar} alt="" />}
                  <AvatarFallback className="text-2xl">{user.name.charAt(0)}</AvatarFallback>
                </Avatar>
                <div className="space-y-2">
                  <p className="text-sm font-medium">{t('profile.avatar')}</p>
                  <div className="flex flex-wrap gap-2">
                    <Button type="button" variant="outline" size="sm" onClick={() => fileInput.current?.click()}>
                      {t('profile.changeAvatar')}
                    </Button>
                    {currentAvatar && (
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => {
                          choose(null)
                          setRemoveAvatar(true)
                        }}
                      >
                        {t('profile.removeAvatar')}
                      </Button>
                    )}
                  </div>
                  <p className="text-xs text-muted-foreground">{t('profile.avatarHint')}</p>
                  <input
                    ref={fileInput}
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    className="hidden"
                    onChange={pickAvatar}
                  />
                  {errors.avatar && <FieldError errors={errors.avatar.map((message) => ({ message }))} />}
                </div>
              </div>

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
                label={t('auth.fields.email')}
                description={t('profile.emailHint')}
                value={user.email}
                readOnly
                disabled
              />
              <FormTextField
                name="grade_level"
                label={t('auth.fields.gradeLevel')}
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
                value={form.parent_phone}
                onChange={set('parent_phone')}
                errors={errors.parent_phone}
              />

              <Button type="submit" disabled={update.isPending}>
                {update.isPending ? t('profile.saving') : t('profile.save')}
              </Button>
            </FieldGroup>
          </form>
        </CardContent>
      </Card>
    </div>
  )
}
