import axios from 'axios'
import { isValidationError } from '@/api/client'

/** Laravel's per-field validation messages (already in the user's language). */
export function getFieldErrors(error: unknown): Record<string, string[]> {
  return isValidationError(error) ? (error.response?.data.errors ?? {}) : {}
}

/**
 * A message for errors that are not tied to a field (network down, 429, 500).
 * Returns null for validation errors, which are shown next to their fields.
 */
export function getGeneralError(error: unknown, fallback: string): string | null {
  if (!error || isValidationError(error)) return null
  if (axios.isAxiosError<{ message?: string }>(error) && error.response?.data?.message) {
    return error.response.data.message
  }
  return fallback
}
