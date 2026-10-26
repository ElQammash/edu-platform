export type QaFilter = 'all' | 'unresolved' | 'mine'

export interface QaAuthor {
  id: number
  name: string
  avatar_url: string | null
}

/** A question (parent_id null, with replies) or a reply. */
export interface LessonComment {
  id: number
  parent_id: number | null
  /** Null when the post was deleted (a deleted question stays while it has replies). */
  body: string | null
  is_deleted: boolean
  /** Posted by the course teacher or an admin. */
  by_staff: boolean
  author: QaAuthor | null
  is_mine: boolean
  created_at: string
  edited_at: string | null
  /** Set while the author can still edit. */
  editable_until: string | null
  can: { edit: boolean; delete: boolean; resolve: boolean; accept: boolean }
  /** Questions only. */
  resolved_at?: string | null
  accepted_reply_id?: number | null
  replies?: LessonComment[]
}

export interface LessonQaNotificationData {
  comment_id: number
  question_id: number
  lesson_id: number
  lesson_title: string
  course_slug: string
  course_title: string
  author_name: string
  excerpt: string
  by_staff?: boolean
  on_my_question?: boolean
}
