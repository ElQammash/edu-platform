import { api } from '@/api/client'
import type { ApiResource } from '@/types/api'

export interface AcademySettings {
  /** Digits only, wa.me format (e.g. 201012345678), or null when not configured. */
  whatsapp_number: string | null
}

export async function fetchSettings(): Promise<AcademySettings> {
  const { data } = await api.get<ApiResource<AcademySettings>>('/settings')
  return data.data
}
