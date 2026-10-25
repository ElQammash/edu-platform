import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import type { Assignment } from '@/types/assignment'
import { fetchAssignment, submitAssignment, type SubmissionInput } from './api'

export const assignmentKeys = {
  detail: (assignmentId: number) => ['assignments', assignmentId] as const,
}

export function useAssignment(assignmentId: number) {
  return useQuery({
    queryKey: assignmentKeys.detail(assignmentId),
    queryFn: () => fetchAssignment(assignmentId),
    enabled: Number.isInteger(assignmentId) && assignmentId > 0,
  })
}

export function useSubmitAssignment(assignmentId: number) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (input: SubmissionInput) => submitAssignment(assignmentId, input),
    onSuccess: (submission) =>
      queryClient.setQueryData<Assignment>(
        assignmentKeys.detail(assignmentId),
        (assignment) => assignment && { ...assignment, submission },
      ),
  })
}
