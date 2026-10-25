import { useMutation, useQueryClient } from '@tanstack/react-query'
import { authKeys } from '@/features/auth/hooks'
import { updateProfile } from './api'

export function useUpdateProfile() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: updateProfile,
    // The profile lives in the current-user cache, so the header updates too.
    onSuccess: (user) => queryClient.setQueryData(authKeys.me, user),
  })
}
