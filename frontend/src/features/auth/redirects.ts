import { env } from '@/lib/env'
import type { User } from '@/types/user'

/**
 * Admins and teachers work in the Filament panels on the backend. They share
 * the same session cookie, so they arrive there already signed in.
 */
export function panelUrlFor(user: User): string | null {
  if (user.roles.includes('admin')) return `${env.apiUrl}/admin`
  if (user.roles.includes('teacher')) return `${env.apiUrl}/teacher`
  return null
}
