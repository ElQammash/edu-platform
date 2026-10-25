import { useQuery } from '@tanstack/react-query'
import { fetchTeacher } from './api'

export function useTeacher(teacherId: number) {
  return useQuery({
    queryKey: ['teachers', teacherId],
    queryFn: () => fetchTeacher(teacherId),
    enabled: Number.isInteger(teacherId) && teacherId > 0,
  })
}
