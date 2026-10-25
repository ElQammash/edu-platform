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

export interface AppNotification {
  id: string
  type: 'submission_graded'
  data: SubmissionGradedData
  read_at: string | null
  created_at: string
}
