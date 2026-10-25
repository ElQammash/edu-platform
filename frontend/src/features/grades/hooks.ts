import { useQuery } from '@tanstack/react-query'
import { fetchMyGrades } from './api'

export function useMyGrades() {
  return useQuery({
    queryKey: ['me', 'grades'],
    queryFn: fetchMyGrades,
  })
}
