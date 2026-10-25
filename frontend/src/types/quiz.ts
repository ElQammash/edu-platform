export type QuestionType = 'mcq' | 'true_false' | 'essay'
export type AttemptStatus = 'in_progress' | 'pending' | 'graded'

export interface QuizSummary {
  id: number
  title: string
  time_limit_minutes: number | null
  due_at: string | null
  is_past_due: boolean
  max_attempts: number
  pass_score: number
  questions_count: number
  total_points: number
}

export interface AttemptSummary {
  id: number
  status: AttemptStatus
  started_at: string
  expires_at: string | null
  submitted_at: string | null
  /** Only once graded. */
  score: number | null
  max_score: number
  percent: number | null
  passed: boolean | null
}

export interface Quiz extends QuizSummary {
  /** Markdown. */
  description: string | null
  lesson: { id: number; title: string }
  course: { id: number; slug: string; title: string }
  attempts?: AttemptSummary[]
  open_attempt_id?: number | null
  can_start: boolean
  cannot_start_reason: string | null
}

export interface AttemptOption {
  id: number
  /** For true/false: the canonical "True" / "False", shown translated. */
  body: string
  /** Only when answers_revealed. */
  is_correct?: boolean
}

export interface AttemptQuestion {
  id: number
  type: QuestionType
  body: string
  points: number
  options: AttemptOption[]
  answer: { option_id: number | null; text_answer: string | null }
  /** Only when answers_revealed. */
  points_awarded?: number
}

export interface Attempt extends AttemptSummary {
  server_time: string
  answers_revealed: boolean
  quiz: { id: number; title: string; pass_score: number; lesson_id: number; course_slug: string }
  questions: AttemptQuestion[]
}

export interface AnswerInput {
  question_id: number
  option_id?: number | null
  text_answer?: string | null
}

export type ComponentStatus = 'graded' | 'pending' | 'missing' | 'open'

export interface ComponentGrade {
  id: number | null
  title: string | null
  status: ComponentStatus
  percent: number | null
  score: number | null
  max_score: number | null
}

export interface LessonGrade {
  lesson: { id: number; title: string }
  percent: number | null
  done: boolean
  assignment: ComponentGrade | null
  quiz: ComponentGrade | null
}

export interface CourseGrades {
  course: { id: number; slug: string; title: string }
  percent: number | null
  done: number
  total: number
  lessons: LessonGrade[]
}
