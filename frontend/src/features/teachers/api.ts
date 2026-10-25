import { api } from '@/api/client'
import type { ApiResource } from '@/types/api'
import type { Teacher } from '@/types/course'

export async function fetchTeacher(teacherId: number): Promise<Teacher> {
  const { data } = await api.get<ApiResource<Teacher>>(`/teachers/${teacherId}`)
  return data.data
}
