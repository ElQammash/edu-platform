import { api } from '@/api/client'
import type { ApiResource } from '@/types/api'
import type { User } from '@/types/user'

export interface ProfileUpdate {
  name: string
  grade_level: string
  parent_phone: string
  avatar?: File | null
  remove_avatar?: boolean
}

/** Multipart POST so the avatar file can be uploaded with the fields. */
export async function updateProfile(update: ProfileUpdate): Promise<User> {
  const form = new FormData()
  form.append('name', update.name)
  if (update.grade_level) form.append('grade_level', update.grade_level)
  if (update.parent_phone) form.append('parent_phone', update.parent_phone)
  if (update.avatar) form.append('avatar', update.avatar)
  if (update.remove_avatar) form.append('remove_avatar', '1')

  const { data } = await api.post<ApiResource<User>>('/me/profile', form)
  return data.data
}
