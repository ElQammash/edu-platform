import axios from 'axios'
import { api, ensureCsrfCookie } from '@/api/client'
import type { ApiResource } from '@/types/api'
import type { User } from '@/types/user'

export interface LoginPayload {
  email: string
  password: string
  remember?: boolean
}

export interface RegisterPayload {
  name: string
  email: string
  password: string
  password_confirmation: string
  grade_level?: string
  parent_phone?: string
}

export async function login(payload: LoginPayload): Promise<User> {
  await ensureCsrfCookie()
  const { data } = await api.post<ApiResource<User>>('/auth/login', payload)
  return data.data
}

export async function register(payload: RegisterPayload): Promise<User> {
  await ensureCsrfCookie()
  const { data } = await api.post<ApiResource<User>>('/auth/register', payload)
  return data.data
}

export async function logout(): Promise<void> {
  await api.post('/auth/logout')
}

/** The signed-in user, or null for guests (the API answers 401). */
export async function fetchMe(): Promise<User | null> {
  try {
    const { data } = await api.get<ApiResource<User>>('/auth/me')
    return data.data
  } catch (error) {
    if (axios.isAxiosError(error) && error.response?.status === 401) return null
    throw error
  }
}

/** Remember the interface language on the account (emails use it). */
export async function saveLocale(locale: 'ar' | 'en'): Promise<void> {
  await api.put('/me/locale', { locale })
}
