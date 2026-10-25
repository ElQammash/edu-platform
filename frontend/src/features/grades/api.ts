import { api } from '@/api/client'
import type { ApiResource } from '@/types/api'
import type { CourseGrades } from '@/types/quiz'

export async function fetchMyGrades(): Promise<CourseGrades[]> {
  const { data } = await api.get<ApiResource<CourseGrades[]>>('/me/grades')
  return data.data
}
