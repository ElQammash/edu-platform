import { api } from '@/api/client'
import type { ApiResource } from '@/types/api'
import type { Assignment, Submission } from '@/types/assignment'

export interface SubmissionInput {
  content: string
  file?: File | null
  removeFile?: boolean
}

export async function fetchAssignment(assignmentId: number): Promise<Assignment> {
  const { data } = await api.get<ApiResource<Assignment>>(`/assignments/${assignmentId}`)
  return data.data
}

/** Multipart POST: creates the submission or updates it until it is graded. */
export async function submitAssignment(assignmentId: number, input: SubmissionInput): Promise<Submission> {
  const form = new FormData()
  form.append('content', input.content)
  if (input.file) form.append('file', input.file)
  if (input.removeFile) form.append('remove_file', '1')

  const { data } = await api.post<ApiResource<Submission>>(`/assignments/${assignmentId}/submission`, form)
  return data.data
}
