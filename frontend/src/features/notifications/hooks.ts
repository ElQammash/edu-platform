import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { fetchNotifications, markAllNotificationsRead, markNotificationRead } from './api'

const notificationKeys = {
  list: ['me', 'notifications'] as const,
}

/** Polls once a minute (and when the tab regains focus) for new notifications. */
export function useNotifications(enabled: boolean) {
  return useQuery({
    queryKey: notificationKeys.list,
    queryFn: fetchNotifications,
    enabled,
    refetchInterval: 60_000,
    refetchOnWindowFocus: true,
  })
}

export function useMarkNotificationRead() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: markNotificationRead,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: notificationKeys.list }),
  })
}

export function useMarkAllNotificationsRead() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: markAllNotificationsRead,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: notificationKeys.list }),
  })
}
