import type { ComponentProps } from 'react'
import { Field, FieldDescription, FieldError, FieldLabel } from '@/components/ui/field'
import { Input } from '@/components/ui/input'

type FormTextFieldProps = Omit<ComponentProps<typeof Input>, 'id'> & {
  name: string
  label: string
  description?: string
  errors?: string[]
}

export function FormTextField({ name, label, description, errors, ...inputProps }: FormTextFieldProps) {
  const id = `field-${name}`
  const invalid = !!errors?.length

  return (
    <Field data-invalid={invalid || undefined}>
      <FieldLabel htmlFor={id}>{label}</FieldLabel>
      <Input
        id={id}
        name={name}
        aria-invalid={invalid || undefined}
        aria-describedby={invalid ? `${id}-error` : undefined}
        {...inputProps}
      />
      {description && <FieldDescription>{description}</FieldDescription>}
      {invalid && <FieldError id={`${id}-error`} errors={errors.map((message) => ({ message }))} />}
    </Field>
  )
}
