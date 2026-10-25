import { api } from '@/api/client'
import type { ApiResource, Paginated } from '@/types/api'
import type { Course, CourseFilters, Enrollment } from '@/types/course'

export async function fetchCourses(filters: CourseFilters): Promise<Paginated<Course>> {
  const { data } = await api.get<Paginated<Course>>('/courses', { params: filters })
  return data
}

export async function fetchCourse(slug: string): Promise<Course> {
  const { data } = await api.get<ApiResource<Course>>(`/courses/${encodeURIComponent(slug)}`)
  return data.data
}

export async function enroll(slug: string): Promise<Enrollment> {
  const { data } = await api.post<ApiResource<Enrollment>>(`/courses/${encodeURIComponent(slug)}/enroll`)
  return data.data
}

export async function fetchMyCourses(): Promise<Enrollment[]> {
  const { data } = await api.get<ApiResource<Enrollment[]>>('/me/courses')
  return data.data
}
