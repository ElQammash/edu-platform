import { api } from '@/api/client'
import type { AppNotification } from '@/types/assignment'

export interface NotificationList {
  data: AppNotification[]
  meta: { unread_count: number }
}

export async function fetchNotifications(): Promise<NotificationList> {
  const { data } = await api.get<NotificationList>('/me/notifications')
  return data
}

export async function markNotificationRead(id: string): Promise<void> {
  await api.post(`/me/notifications/${encodeURIComponent(id)}/read`)
}

export async function markAllNotificationsRead(): Promise<void> {
  await api.post('/me/notifications/read-all')
}
