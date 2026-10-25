import axios, { AxiosError, type InternalAxiosRequestConfig } from 'axios'
import i18n from '@/lib/i18n'
import { env } from '@/lib/env'
import type { ValidationErrorBody } from '@/types/api'

/**
 * Axios instance for the Laravel API, configured for Sanctum SPA auth:
 * - withCredentials sends the session cookie to the API origin;
 * - withXSRFToken copies the XSRF-TOKEN cookie into the X-XSRF-TOKEN header,
 *   which axios only does for same-origin requests unless this is set.
 */
export const api = axios.create({
  baseURL: `${env.apiUrl}/api/v1`,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
})

/** Ask Laravel for a fresh XSRF-TOKEN cookie. Call before login/register. */
export function ensureCsrfCookie() {
  return axios.get(`${env.apiUrl}/sanctum/csrf-cookie`, { withCredentials: true })
}

// Laravel picks the response language (validation messages, errors) from this.
api.interceptors.request.use((config) => {
  config.headers['Accept-Language'] = i18n.resolvedLanguage ?? 'ar'
  return config
})

// 419 means the CSRF token expired: fetch a new cookie and retry once.
api.interceptors.response.use(undefined, async (error: AxiosError) => {
  const config = error.config as (InternalAxiosRequestConfig & { _csrfRetried?: boolean }) | undefined

  if (error.response?.status === 419 && config && !config._csrfRetried) {
    config._csrfRetried = true
    await ensureCsrfCookie()
    return api(config)
  }

  return Promise.reject(error)
})

export function isValidationError(error: unknown): error is AxiosError<ValidationErrorBody> {
  return axios.isAxiosError(error) && error.response?.status === 422
}
