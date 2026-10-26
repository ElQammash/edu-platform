import type { PaymentNotificationData } from './payment'
import type { LessonQaNotificationData } from './qa'

export interface AssignmentSummary {
  id: number
  title: string
  due_at: string | null
  is_past_due: boolean
  max_score: number
}

export interface SubmissionFile {
  name: string
  mime: string
  size: number
  /** Authorized API endpoint; opens with the session cookie. */
  url: string
}

export interface Submission {
  id: number
  content: string | null
  file: SubmissionFile | null
  submitted_at: string
  is_late: boolean
  is_graded: boolean
  score: number | null
  feedback: string | null
  graded_at: string | null
  can_edit: boolean
}

export interface Assignment extends AssignmentSummary {
  /** Markdown. */
  instructions: string | null
  lesson: { id: number; title: string }
  course: { id: number; slug: string; title: string }
  /** The student's own submission; null before they submit. */
  submission?: Submission | null
}

export interface SubmissionGradedData {
  submission_id: number
  assignment_id: number
  assignment_title: string
  lesson_id: number
  course_slug: string
  course_title: string
  score: number
  max_score: number
  regraded: boolean
}

export interface QuizGradedData {
  attempt_id: number
  quiz_id: number
  quiz_title: string
  lesson_id: number
  course_slug: string
  course_title: string
  score: number
  max_score: number
  regraded: boolean
}

export interface LiveLessonNotificationData {
  lesson_id: number
  lesson_title: string
  course_id: number
  course_slug: string
  course_title: string
  starts_at: string | null
  /** Reminders: 24h or 1h before. */
  kind?: '24h' | '1h'
  /** Reschedules: the old start time. */
  previous_starts_at?: string | null
}

interface NotificationBase {
  id: string
  read_at: string | null
  created_at: string
}

export type AppNotification =
  | (NotificationBase & { type: 'submission_graded'; data: SubmissionGradedData })
  | (NotificationBase & { type: 'quiz_graded'; data: QuizGradedData })
  | (NotificationBase & {
      type: 'live_lesson_reminder' | 'live_lesson_rescheduled' | 'live_lesson_cancelled'
      data: LiveLessonNotificationData
    })
  | (NotificationBase & {
      type: 'lesson_question' | 'lesson_reply' | 'lesson_answer_accepted'
      data: LessonQaNotificationData
    })
  | (NotificationBase & { type: 'payment_confirmed' | 'payment_refunded'; data: PaymentNotificationData })
