import axios from 'axios'
import { isValidationError } from '@/api/client'

/** Laravel's per-field validation messages (already in the user's language). */
export function getFieldErrors(error: unknown): Record<string, string[]> {
  const errors = isValidationError(error) ? error.response?.data?.errors : undefined
  return errors && typeof errors === 'object' ? errors : {}
}

/**
 * A message for errors that are not tied to a field (network down, 429, 500).
 * Returns null for validation errors shown next to their fields; a 422
 * without field errors (e.g. an unparsable body) still gets the fallback,
 * so the user is never left without feedback.
 */
export function getGeneralError(error: unknown, fallback: string): string | null {
  if (!error) return null
  if (isValidationError(error) && Object.keys(getFieldErrors(error)).length > 0) return null
  if (axios.isAxiosError<{ message?: string }>(error) && typeof error.response?.data?.message === 'string') {
    return error.response.data.message
  }
  return fallback
}
