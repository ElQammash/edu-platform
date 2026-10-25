import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import type { Role } from '@/types/user'
import { fetchMe, login, logout, register } from './api'

export const authKeys = {
  me: ['auth', 'me'] as const,
}

export function useMe() {
  return useQuery({
    queryKey: authKeys.me,
    queryFn: fetchMe,
    staleTime: 5 * 60_000,
  })
}

/** Convenience wrapper around useMe for components that only need auth state. */
export function useAuth() {
  const { data: user, isPending } = useMe()

  return {
    user: user ?? null,
    isLoading: isPending,
    isAuthenticated: !!user,
    hasRole: (role: Role) => user?.roles.includes(role) ?? false,
  }
}

export function useLogin() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: login,
    onSuccess: (user) => queryClient.setQueryData(authKeys.me, user),
  })
}

export function useRegister() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: register,
    onSuccess: (user) => queryClient.setQueryData(authKeys.me, user),
  })
}

export function useLogout() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: logout,
    onSuccess: () => {
      // Drop everything cached for the previous user, then mark as guest.
      queryClient.clear()
      queryClient.setQueryData(authKeys.me, null)
    },
  })
}
